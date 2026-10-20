<?php

namespace ZaberDev\Context\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\Database\Eloquent\Model;
use ZaberDev\Context\DTO\ContextInfo;
use ZaberDev\Context\Contracts\ContextResolver;
use ZaberDev\Context\Contracts\ContextDriver;

/**
 * @method static void push(Model $context, string $source = 'manual')
 * @method static Model|null pop()
 * @method static Model|null replace(Model $context, string $source = 'manual')
 * @method static Model|null current()
 * @method static Model require()
 * @method static ContextInfo info()
 * @method static mixed execute(Model $context, callable $callback, string $source = 'manual')
 * @method static mixed transaction(Model $context, callable $callback, string $source = 'manual')
 * @method static void resolver(ContextResolver|callable $resolver)
 * @method static Model|null resolve()
 * @method static array all()
 * @method static void set(Model $context, string $source = 'manual')
 * @method static void clear()
 * @method static array snapshot()
 * @method static void restore(array $snapshot)
 * @method static \Closure capture(callable $callback)
 * @method static ContextDriver driver(string|null $driver = null)
 *
 * @see \ZaberDev\Context\ContextManager
 * @see \ZaberDev\Context\Contracts\ContextDriver
 */
class Context extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'context.manager';
    }
}
