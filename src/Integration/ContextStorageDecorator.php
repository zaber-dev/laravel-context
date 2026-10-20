<?php

namespace ZaberDev\Context\Integration;

use Illuminate\Contracts\Filesystem\Filesystem;

class ContextStorageDecorator
{
    public function __construct(
        protected Filesystem $disk,
        protected string $prefix
    ) {}

    protected function prefixPath(string $path): string
    {
        return $this->prefix . ltrim($path, '/');
    }

    public function exists($path)
    {
        return $this->disk->exists($this->prefixPath($path));
    }

    public function get($path)
    {
        return $this->disk->get($this->prefixPath($path));
    }

    public function put($path, $contents, $options = [])
    {
        return $this->disk->put($this->prefixPath($path), $contents, $options);
    }

    public function delete($paths)
    {
        $paths = is_array($paths) ? $paths : func_get_args();
        
        $prefixedPaths = array_map(fn($path) => $this->prefixPath($path), $paths);
        
        return $this->disk->delete($prefixedPaths);
    }
    
    public function url($path)
    {
        if (method_exists($this->disk, 'url')) {
            return $this->disk->url($this->prefixPath($path));
        }
        
        return null;
    }
    
    public function size($path)
    {
        return $this->disk->size($this->prefixPath($path));
    }
    
    // Forward any other calls
    public function __call($method, $parameters)
    {
        if (isset($parameters[0]) && is_string($parameters[0])) {
            $parameters[0] = $this->prefixPath($parameters[0]);
        }
        
        return $this->disk->$method(...$parameters);
    }
}
