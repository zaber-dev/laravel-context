<?php

namespace ZaberDev\Context\Contracts;

use Illuminate\Database\Eloquent\Model;
use ZaberDev\Context\DTO\ContextInfo;

interface ContextDriver
{
    public function push(Model $context, string $source = 'manual'): void;
    public function pop(): ?Model;
    public function replace(Model $context, string $source = 'manual'): ?Model;
    
    public function current(): ?Model;
    public function info(): ContextInfo;
    public function all(): array;
    
    public function set(Model $context, string $source = 'manual'): void;
    public function clear(): void;
    
    public function execute(Model $context, callable $callback, string $source = 'manual');
    public function transaction(Model $context, callable $callback, string $source = 'manual');
    public function require(): Model;
    
    public function snapshot(): array;
    public function restore(array $snapshot): void;
    public function capture(callable $callback): \Closure;
}
