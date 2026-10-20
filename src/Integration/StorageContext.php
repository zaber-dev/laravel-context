<?php

namespace ZaberDev\Context\Integration;

use Illuminate\Support\Facades\Storage;
use ZaberDev\Context\Facades\Context;

class StorageContext
{
    public static function register(): void
    {
        $macro = function () {
            /** @var \Illuminate\Filesystem\FilesystemAdapter|\Illuminate\Filesystem\FilesystemManager $this */
            $context = Context::current();
            
            if (! $context) {
                return $this instanceof \Illuminate\Filesystem\FilesystemManager ? $this->disk() : $this;
            }
            
            $prefix = $context->getMorphClass() . '/' . $context->getKey() . '/';
            
            $disk = $this instanceof \Illuminate\Filesystem\FilesystemManager ? $this->disk() : $this;
            
            return new ContextStorageDecorator($disk, $prefix);
        };
        
        Storage::macro('context', $macro);
        \Illuminate\Filesystem\FilesystemAdapter::macro('context', $macro);
    }
}
