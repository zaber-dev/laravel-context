# Learn: Ambient Execution Context & Tenant Boundaries in Laravel

Welcome to the architectural learning guide for **Laravel Context** (`zaber-dev/laravel-context`). Whether you are evaluating this package for multi-tenant SaaS architectures or studying modern Laravel package engineering for GitHub Community Exchange, this document provides a deep dive into the design patterns, stack management, and framework integration hooks powering the project.

---

## 1. Core Architectural Goals

In enterprise applications, operations rarely run in a vacuum. Every query, cache write, storage upload, log entry, and queued job belongs to an **execution boundary** — a Tenant, Workspace, Organization, or Project.

Historically, applications handle this by passing context parameters down every layer of the call stack:

```php
// The Leaky Parameter Anti-Pattern
$controller->handle($workspace, $request);
$service->process($workspace, $data);
$repository->save($workspace, $model);
Cache::put("workspace_{$workspace->id}_stats", $stats);
```

**Laravel Context** replaces parameter drilling with **Ambient Execution Boundaries**. Once an execution boundary is pushed onto the context stack, all supported framework features automatically inherit the boundary without modifying function signatures.

---

## 2. Manager & Driver Architecture (`Illuminate\Support\Manager`)

`ContextManager` extends Laravel's `Illuminate\Support\Manager`, exposing fluent API access to context drivers while providing dynamic resolver hooks:

```
                     +--------------------------+
                     |      Context (Facade)    |
                     +------------+-------------+
                                  |
                                  v
                     +--------------------------+
                     |      ContextManager      |
                     +------------+-------------+
                                  |
                                  v
                     +--------------------------+
                     |   MemoryContextDriver    |
                     +------------+-------------+
                                  |
                                  v
                     +--------------------------+
                     |    $contextStack = []    |
                     +--------------------------+
```

### The Context Stack

Inside `MemoryContextDriver`, active boundaries are tracked in an internal stack array:

```php
protected array $contextStack = [];
```

Each stack element stores:
- `model`: The Eloquent entity representing the boundary (e.g., `App\Models\Workspace:12`).
- `source`: Origin marker (`'manual'`, `'resolver'`, `'route'`).
- `startedAt`: High-resolution microtime float.
- `boundaryId`: Unique UUID generated for distributed tracing.

Calling `Context::push($model)` pushes a new boundary onto the stack, while `Context::pop()` removes it, restoring the parent boundary. This allows seamless nesting of execution boundaries (e.g., an Organization boundary containing a Workspace boundary).

---

## 3. Deep Framework Integration Mechanics

When `ContextServiceProvider` boots, it registers hooks across four major Laravel framework components:

```
                              Context Stack
                                    │
       ┌───────────────┬────────────┴───────────┬───────────────┐
       ▼               ▼                        ▼               ▼
 Queue Integration  Cache Integration   Storage Integration  Log Integration
 (`QueueContext`)  (`CacheDecorator`)   (`StorageDecorator`) (`LogContext`)
```

### A. Queue Propagation (`QueueContext`)

Queued jobs execute in separate PHP CLI worker processes long after the HTTP request finishes. To preserve context across processes:

1. **Job Dispatch Hook**: `QueueContext` listens to `JobQueueing` / `JobProcessing` events.
2. **Payload Injection**: It serializes the active context stack snapshot into the job's payload under `_context_snapshot`.
3. **Worker Restoration**: When the queue worker picks up the job, `QueueContext` inspects `_context_snapshot` and automatically runs `Context::restore($snapshot)` before the job's `handle()` method executes.
4. **Cleanup**: When the job finishes or fails, the worker pops the context stack cleanly.

### B. Cache Isolation (`ContextCacheDecorator`)

`Cache::context()` returns a `ContextCacheDecorator` wrapping Laravel's cache repository:

```php
public function remember($key, $ttl, Closure $callback)
{
    $scopedKey = $this->formatKey($key);
    return $this->repository->remember($scopedKey, $ttl, $callback);
}

protected function formatKey(string $key): string
{
    $current = Context::current();
    if (! $current) {
        return $key;
    }

    $type = strtolower(class_basename($current));
    return "{$type}s:{$current->getKey()}:{$key}";
}
```

This transforms raw cache keys like `'stats'` into isolated keys like `'workspaces:12:stats'`.

### C. Storage Isolation (`ContextStorageDecorator`)

`Storage::disk()->context()` wraps filesystem drivers to dynamically prefix paths:

```php
// Uploads to: "workspaces/12/reports/summary.pdf"
Storage::disk('public')->context()->put('reports/summary.pdf', $data);
```

### D. Log Telemetry Injection (`LogContext`)

`LogContext` listens to `MessageLogged` and context lifecycle events, injecting active boundary details into Laravel's `Log::withContext()` array:

```php
Log::withContext([
    'context_type' => get_class($current),
    'context_id'   => $current->getKey(),
    'boundary_id'  => $info->boundaryId,
]);
```

Every log line written inside an execution boundary automatically records tenant metadata and correlation UUIDs for OpenTelemetry or Logstash indexing.

---

## 4. Snapshot Captures & Async Closures

When passing callbacks into asynchronous pipelines or event dispatchers, `Context::capture()` creates a state closure:

```php
public function capture(callable $callback): \Closure
{
    $snapshot = $this->snapshot();
    
    return function (...$args) use ($snapshot, $callback) {
        $previous = $this->snapshot();
        $this->restore($snapshot);
        
        try {
            return $callback(...$args);
        } finally {
            $this->restore($previous);
        }
    };
}
```

1. **Snapshot on Capture**: Captures the exact context stack state at the time `Context::capture()` is called.
2. **Restore on Invocation**: When the returned closure is executed later, it saves the current context stack, restores the captured snapshot, executes `$callback()`, and restores the original stack inside a `finally` block.

---

## 5. Correlation IDs & Distributed Tracing

Every time a boundary is pushed onto the stack (`Context::push()`), `MemoryContextDriver` generates a UUID v4 string:

```php
'boundaryId' => (string) \Illuminate\Support\Str::uuid()
```

This `boundaryId` acts as a **Correlation ID**. Because it is serialized into queue job payloads and injected into loggers, developers can trace a request from its initial HTTP request through multiple background queue jobs and microservices using a single correlation UUID.

---

## 6. Context-Aware Eloquent Models (`IsContextual`)

The `IsContextual` trait integrates models directly with the active execution boundary:

```php
trait IsContextual
{
    public static function bootIsContextual(): void
    {
        static::creating(function (Model $model) {
            if ($context = Context::current()) {
                $model->context_id = $context->getKey();
                $model->context_type = $context->getMorphClass();
            }
        });

        static::addGlobalScope('contextual', function (Builder $builder) {
            if ($context = Context::current()) {
                $builder->where('context_id', $context->getKey())
                        ->where('context_type', $context->getMorphClass());
            }
        });
    }
}
```

1. **Auto-Assignment**: When saving a new contextual model, `creating` hooks auto-populate `context_id` and `context_type`.
2. **Global Query Scoping**: All queries automatically append `WHERE context_id = ? AND context_type = ?`, eliminating cross-tenant data leakage.

---

## 7. Contextual Database Transactions

`Context::transaction($model, $callback)` combines database transaction boundaries with context boundary management:

```php
public function transaction(Model $context, callable $callback, string $source = 'manual')
{
    $this->push($context, $source);
    
    DB::beginTransaction();
    
    try {
        $result = $callback();
        DB::commit();
        return $result;
    } catch (\Throwable $e) {
        DB::rollBack();
        throw $e;
    } finally {
        $this->pop();
    }
}
```

If an exception occurs within the callback, the database transaction is automatically rolled back and the context stack is popped safely inside the `finally` block.
