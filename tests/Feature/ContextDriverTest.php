<?php

namespace ZaberDev\Context\Tests\Feature;

use Illuminate\Support\Facades\Event;
use ZaberDev\Context\Facades\Context;
use ZaberDev\Context\Events\ContextPushed;
use ZaberDev\Context\Events\ContextPopped;
use ZaberDev\Context\Events\ContextSwapped;
use ZaberDev\Context\Exceptions\ContextMissingException;
use ZaberDev\Context\Tests\TestCase;

class ContextDriverTest extends TestCase
{
    public function test_transaction_commits_successfully()
    {
        $workspace = Workspace::create(['name' => 'WS Tx']);
        
        $result = Context::transaction($workspace, function () {
            $this->assertNotNull(Context::current());
            // Create something in DB
            Task::create(['title' => 'Inside Tx']);
            return 'tx-success';
        });
        
        $this->assertEquals('tx-success', $result);
        $this->assertNull(Context::current());
        $this->assertDatabaseHas('tasks', ['title' => 'Inside Tx']);
    }

    public function test_transaction_rolls_back_on_exception()
    {
        $workspace = Workspace::create(['name' => 'WS Tx Fail']);
        
        try {
            Context::transaction($workspace, function () {
                Task::create(['title' => 'Rollback Me']);
                throw new \Exception('Tx Failed');
            });
        } catch (\Exception $e) {
            $this->assertEquals('Tx Failed', $e->getMessage());
        }
        
        $this->assertNull(Context::current());
        $this->assertDatabaseMissing('tasks', ['title' => 'Rollback Me']);
    }

    public function test_replace_replaces_context_and_fires_event()
    {
        Event::fake([ContextSwapped::class]);
        
        $w1 = Workspace::create(['name' => 'W1']);
        $w2 = Workspace::create(['name' => 'W2']);
        
        Context::push($w1);
        
        $popped = Context::replace($w2);
        
        $this->assertEquals($w1->id, $popped->id);
        $this->assertEquals($w2->id, Context::current()->id);
        
        Event::assertDispatched(ContextSwapped::class, function ($event) use ($w1, $w2) {
            return $event->old && $event->old->id === $w1->id && $event->new->id === $w2->id;
        });
    }

    public function test_require_throws_when_empty()
    {
        $this->expectException(ContextMissingException::class);
        Context::require();
    }
    
    public function test_require_returns_current()
    {
        $w1 = Workspace::create(['name' => 'W1']);
        Context::push($w1);
        
        $this->assertEquals($w1->id, Context::require()->id);
    }
    
    public function test_execute_method_pushes_and_pops_context()
    {
        $w1 = Workspace::create(['name' => 'W1']);
        
        $result = Context::execute($w1, function () use ($w1) {
            $this->assertEquals($w1->id, Context::current()->id);
            return 'done';
        });
        
        $this->assertEquals('done', $result);
        $this->assertNull(Context::current());
    }    
    
    public function test_snapshot_and_restore()
    {
        $w1 = Workspace::create(['name' => 'W1']);
        $w2 = Workspace::create(['name' => 'W2']);
        
        Context::push($w1);
        Context::push($w2);
        
        $snapshot = Context::snapshot();
        
        Context::clear();
        $this->assertNull(Context::current());
        
        Context::restore($snapshot);
        $this->assertEquals($w2->id, Context::current()->id);
        $this->assertCount(2, Context::all());
    }

    public function test_capture_wraps_execution_boundary()
    {
        $w1 = Workspace::create(['name' => 'W1']);
        $w2 = Workspace::create(['name' => 'W2']);
        
        Context::push($w1);
        
        $closure = Context::capture(function () {
            return Context::current()->name;
        });
        
        // Change context
        Context::push($w2);
        
        // Execute closure (should run inside captured boundary w1)
        $result = $closure();
        
        $this->assertEquals('W1', $result);
        
        // Current should still be w2
        $this->assertEquals('W2', Context::current()->name);
    }
}
