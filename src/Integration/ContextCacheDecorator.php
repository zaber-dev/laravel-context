<?php

namespace ZaberDev\Context\Integration;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

class ContextCacheDecorator
{
    public function __construct(
        protected CacheRepository $cache,
        protected string $prefix
    ) {}

    protected function prefixKey(string $key): string
    {
        return $this->prefix . $key;
    }

    public function get($key, $default = null)
    {
        return $this->cache->get($this->prefixKey($key), $default);
    }

    public function put($key, $value, $ttl = null)
    {
        return $this->cache->put($this->prefixKey($key), $value, $ttl);
    }
    
    public function remember($key, $ttl, \Closure $callback)
    {
        return $this->cache->remember($this->prefixKey($key), $ttl, $callback);
    }

    public function rememberForever($key, \Closure $callback)
    {
        return $this->cache->rememberForever($this->prefixKey($key), $callback);
    }

    public function forget($key)
    {
        return $this->cache->forget($this->prefixKey($key));
    }
    
    public function pull($key, $default = null)
    {
        return $this->cache->pull($this->prefixKey($key), $default);
    }

    public function has($key)
    {
        return $this->cache->has($this->prefixKey($key));
    }

    public function increment($key, $value = 1)
    {
        return $this->cache->increment($this->prefixKey($key), $value);
    }
    
    public function decrement($key, $value = 1)
    {
        return $this->cache->decrement($this->prefixKey($key), $value);
    }

    public function forever($key, $value)
    {
        return $this->cache->forever($this->prefixKey($key), $value);
    }
    
    public function flush()
    {
        // Note: flushing a context-prefixed cache isn't natively supported 
        // without tags, so we delegate to flush() which will clear ALL cache.
        // Be careful with this!
        return $this->cache->flush();
    }
}
