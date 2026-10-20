<?php

namespace ZaberDev\Context\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use ZaberDev\Context\Facades\Context;
use ZaberDev\Context\Tests\TestCase;
use ZaberDev\Context\Traits\IsContextual;
use ZaberDev\Context\Traits\HasContextualModels;

class Workspace extends Model
{
    use HasContextualModels;
    
    protected $guarded = [];

    public function tasks()
    {
        return $this->contextual(Task::class);
    }
}

class Task extends Model
{
    use IsContextual;
    
    protected $guarded = [];
}

class ContextualModelTest extends TestCase
{
    public function test_it_automatically_assigns_context_when_creating()
    {
        $workspace = Workspace::create(['name' => 'Laravel Context']);
        
        Context::set($workspace);

        $task = Task::create(['title' => 'Build package']);

        $this->assertEquals(Workspace::class, $task->context_type);
        $this->assertEquals($workspace->id, $task->context_id);
    }

    public function test_it_automatically_scopes_queries_to_active_context()
    {
        $workspace1 = Workspace::create(['name' => 'Workspace 1']);
        $workspace2 = Workspace::create(['name' => 'Workspace 2']);

        Context::set($workspace1);
        Task::create(['title' => 'Task for WS 1']);

        Context::set($workspace2);
        Task::create(['title' => 'Task for WS 2']);

        Context::set($workspace1);
        
        $tasks = Task::all();
        
        $this->assertCount(1, $tasks);
        $this->assertEquals('Task for WS 1', $tasks->first()->title);
    }

    public function test_without_context_scope()
    {
        $workspace1 = Workspace::create(['name' => 'Workspace 1']);
        
        Context::set($workspace1);
        Task::create(['title' => 'Task for WS 1']);

        Context::clear();
        
        $tasks = Task::all(); // Should return nothing if context is not set, wait...
        // Actually, if Context::current() is null, the global scope adds NO constraints.
        // Let's test that if Context is null, it returns all.
        $this->assertCount(1, $tasks);
        
        Context::set($workspace1);
        
        $tasksWithoutContext = Task::withoutContext()->get();
        $this->assertCount(1, $tasksWithoutContext);
    }

    public function test_has_contextual_models_relationship()
    {
        $workspace = Workspace::create(['name' => 'Relationship Test']);
        
        $task = $workspace->tasks()->create(['title' => 'Relation Task']);

        $this->assertEquals(Workspace::class, $task->context_type);
        $this->assertEquals($workspace->id, $task->context_id);
    }
}
