<?php

namespace ZaberDev\Context\Drivers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use ZaberDev\Context\Contracts\ContextDriver;
use ZaberDev\Context\DTO\ContextInfo;
use ZaberDev\Context\Exceptions\ContextMissingException;
use ZaberDev\Context\ContextManager;

class MemoryContextDriver implements ContextDriver
{
    /**
     * The stack of active contexts.
     *
     * @var array<int, array{model: Model, source: string, group: array<int, Model>, startedAt: float}>
     */
    protected array $contextStack = [];
    
    public function __construct(protected ContextManager $manager, protected string $name = 'memory') {}

    public function push(Model $context, string $source = 'manual'): void
    {
        $this->contextStack[] = [
            'model' => $context,
            'source' => $source,
            'startedAt' => microtime(true),
            'boundaryId' => (string) \Illuminate\Support\Str::uuid()
        ];
        
        $this->manager->fireEvent('ContextPushed', ['context' => $context, 'source' => $source]);
    }

    public function pop(): ?Model
    {
        $contextData = array_pop($this->contextStack);
        $model = $contextData ? $contextData['model'] : null;
        
        if ($model) {
            $this->manager->fireEvent('ContextPopped', ['context' => $model]);
        }
        
        return $model;
    }

    public function replace(Model $context, string $source = 'manual'): ?Model
    {
        $popped = $this->pop();
        $this->push($context, $source);
        
        $this->manager->fireEvent('ContextSwapped', ['new' => $context, 'old' => $popped]);
        
        return $popped;
    }

    public function current(): ?Model
    {
        $context = end($this->contextStack);
        if ($context !== false) {
            return $context['model'];
        }
        
        // Let manager try to resolve if empty
        $resolved = $this->manager->resolve();
        if ($resolved) {
            // Implicitly push? Usually no, but maybe return it.
            // If the manager resolves it, we can just return it.
            // But wait, the manager's resolve method is just calling resolvers. 
            // The driver should probably just return the resolved model.
            return $resolved;
        }
        
        return null;
    }
    


    public function info(): ContextInfo
    {
        $current = $this->current();
        
        $stackModels = array_map(fn($item) => $item['model'], $this->contextStack);
        
        $top = end($this->contextStack);
        $source = $top !== false ? $top['source'] : null;
        $startedAt = $top !== false ? $top['startedAt'] : null;
        $boundaryId = $top !== false ? $top['boundaryId'] : null;
        
        $parent = count($stackModels) > 1 ? $stackModels[count($stackModels) - 2] : null;
        $root = count($stackModels) > 0 ? $stackModels[0] : null;
        
        return new ContextInfo(
            current: $current,
            stack: $stackModels,
            depth: count($this->contextStack),
            source: $source,
            parent: $parent,
            root: $root,
            driver: $this->name,
            startedAt: $startedAt,
            boundaryId: $boundaryId
        );
    }

    public function all(): array
    {
        return array_map(fn($item) => $item['model'], $this->contextStack);
    }

    public function set(Model $context, string $source = 'manual'): void
    {
        $this->contextStack = [];
        $this->push($context, $source);
    }

    public function clear(): void
    {
        $this->contextStack = [];
    }



    public function execute(Model $context, callable $callback, string $source = 'manual')
    {
        $this->push($context, $source);
        
        try {
            return $callback();
        } finally {
            $this->pop();
        }
    }

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

    public function require(): Model
    {
        $current = $this->current();
        
        if (! $current) {
            throw new ContextMissingException();
        }
        
        return $current;
    }

    public function snapshot(): array
    {
        return $this->contextStack;
    }

    public function restore(array $snapshot): void
    {
        $this->contextStack = $snapshot;
    }

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
}
