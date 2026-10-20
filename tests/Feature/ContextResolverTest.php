<?php

namespace ZaberDev\Context\Tests\Feature;

use Illuminate\Http\Request;
use ZaberDev\Context\Facades\Context;
use ZaberDev\Context\Resolvers\RequestResolver;
use ZaberDev\Context\Tests\TestCase;

class ContextResolverTest extends TestCase
{
    public function test_request_resolver_can_resolve_context_from_attributes()
    {
        $workspace = Workspace::create(['name' => 'Resolved WS']);
        
        $request = new Request();
        $request->attributes->set('context', $workspace);
        
        $resolver = new RequestResolver($request);
        Context::resolver($resolver);
        
        $resolved = Context::current();
        
        $this->assertNotNull($resolved);
        $this->assertEquals($workspace->id, $resolved->id);
    }
}
