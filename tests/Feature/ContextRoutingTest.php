<?php

namespace ZaberDev\Context\Tests\Feature;

use Illuminate\Support\Facades\Route;
use ZaberDev\Context\Facades\Context;
use ZaberDev\Context\Http\Middleware\SetContext;
use ZaberDev\Context\Tests\TestCase;

class ContextRoutingTest extends TestCase
{
    public function test_middleware_sets_context_from_route_parameter()
    {
        $workspace = Workspace::create(['name' => 'Routing WS']);
        
        Route::middleware([
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            SetContext::class . ':workspace',
        ])->get('/workspaces/{workspace}/tasks', function (Workspace $workspace) {
            $context = Context::current();
            return response()->json([
                'context_type' => $context ? $context->getMorphClass() : null,
                'context_id' => $context ? $context->getKey() : null,
            ]);
        });

        $response = $this->get('/workspaces/' . $workspace->id . '/tasks');

        $response->assertStatus(200);
        $response->assertJson([
            'context_type' => Workspace::class,
            'context_id' => $workspace->id,
        ]);
    }

    public function test_route_model_binding_automatically_scopes_to_context()
    {
        $workspace1 = Workspace::create(['name' => 'Routing WS 1']);
        $workspace2 = Workspace::create(['name' => 'Routing WS 2']);

        Context::set($workspace1);
        $task1 = Task::create(['title' => 'Task 1']);

        Context::set($workspace2);
        $task2 = Task::create(['title' => 'Task 2']);

        // Since the middleware sets the context before the task is resolved implicitly,
        // and Task uses IsContextual which applies global scope,
        // requesting task2 in workspace1 context should return 404!
        
        Route::middleware([
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            SetContext::class . ':workspace',
        ])->get('/workspaces/{workspace}/tasks/{task}', function (Workspace $workspace, Task $task) {
                 return response()->json(['task_id' => $task->id]);
             });

        Context::clear();

        $response1 = $this->get('/workspaces/' . $workspace1->id . '/tasks/' . $task1->id);
        $response1->assertStatus(200);
        $response1->assertJson(['task_id' => $task1->id]);

        $response2 = $this->get('/workspaces/' . $workspace1->id . '/tasks/' . $task2->id);
        $response2->assertStatus(404);
    }
}
