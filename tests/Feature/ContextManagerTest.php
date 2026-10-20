<?php

namespace ZaberDev\Context\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use ZaberDev\Context\Facades\Context;
use ZaberDev\Context\Tests\TestCase;

class ContextManagerTest extends TestCase
{
    public function test_it_can_push_and_pop_contexts()
    {
        $workspace = Workspace::create(['name' => 'WS 1']);
        $project = Workspace::create(['name' => 'Project 1']); // Pretend it's a project
        
        Context::push($workspace);
        $this->assertEquals($workspace->id, Context::current()->id);
        
        Context::push($project, 'queue');
        $this->assertEquals($project->id, Context::current()->id);
        
        $info = Context::info();
        $this->assertEquals(2, $info->depth);
        $this->assertEquals('queue', $info->source);
        $this->assertCount(2, $info->stack);
        
        $popped = Context::pop();
        $this->assertEquals($project->id, $popped->id);
        
        $this->assertEquals($workspace->id, Context::current()->id);
        
        $info = Context::info();
        $this->assertEquals(1, $info->depth);
        $this->assertEquals('manual', $info->source);
    }

    public function test_it_can_execute_callback_within_context()
    {
        $workspace = Workspace::create(['name' => 'Run Test']);
        
        $result = Context::execute($workspace, function () use ($workspace) {
            $this->assertEquals($workspace->id, Context::current()->id);
            return 'executed';
        });
        
        $this->assertEquals('executed', $result);
        $this->assertNull(Context::current());
    }

    public function test_it_resolves_context_from_registered_resolvers()
    {
        $workspace = Workspace::create(['name' => 'WS Resolved']);
        
        Context::resolver(function () use ($workspace) {
            return $workspace;
        });
        
        // Even without pushing, it should resolve the current context
        $this->assertEquals($workspace->id, Context::current()->id);
    }
}
