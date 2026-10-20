<?php

namespace ZaberDev\Context\Integration;

use Illuminate\Support\Facades\Cache;
use ZaberDev\Context\Facades\Context;

class CacheContext
{
    public static function register(): void
    {
        Cache::macro('context', function () {
            $context = Context::current();
            
            if (! $context) {
                return Cache::store();
            }
            
            $prefix = sprintf('%s:%s:', $context->getMorphClass(), $context->getKey());
            
            return new ContextCacheDecorator(Cache::store(), $prefix);
        });
    }
}
