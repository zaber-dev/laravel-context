<?php

namespace ZaberDev\Context\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use ZaberDev\Context\Facades\Context;
use ZaberDev\Context\Tests\TestCase;

class CacheIntegrationTest extends TestCase
{
    public function test_it_prefixes_cache_keys_with_context()
    {
        $workspace = Workspace::create(['name' => 'Cache WS']);
        
        Context::set($workspace);
        
        Cache::context()->put('test-key', 'value-in-context');
        
        $this->assertEquals('value-in-context', Cache::context()->get('test-key'));
        
        // In the root cache, it should have the prefix
        $prefix = $workspace->getMorphClass() . ':' . $workspace->getKey() . ':';
        $this->assertEquals('value-in-context', Cache::get($prefix . 'test-key'));
        
        // Without context, the plain key is empty
        $this->assertNull(Cache::get('test-key'));
        
        // Clear context, should fallback to root cache
        Context::clear();
        $this->assertNull(Cache::context()->get('test-key'));
    }
}
