<?php

namespace ZaberDev\Context\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use ZaberDev\Context\Facades\Context;
use ZaberDev\Context\Tests\TestCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class DummyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle()
    {
        // Job runs, we just need to ensure context is restored!
        // We can capture context in a static variable for testing purposes.
        static::$lastContext = Context::info();
    }
    
    public static $lastContext = null;
}

class QueueIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DummyJob::$lastContext = null;
    }

    public function test_it_serializes_and_restores_context_in_queue_jobs()
    {
        $workspace = Workspace::create(['name' => 'Queue WS']);
        
        Context::push($workspace, 'manual');
        
        // Dispatch the job synchronously for testing
        // Wait, if it runs synchronously, the payload might not be serialized and deserialized using the Queue hooks.
        // We should push to the database or sync connection that fires events.
        
        // Actually, 'sync' connection does trigger createPayloadUsing in some Laravel versions, but to be safe, let's just manually test the event hooks or dispatch.
        // Let's use the 'sync' driver and see if hooks are executed.
        config(['queue.default' => 'sync']);
        
        DummyJob::dispatch();
        
        $this->assertNotNull(DummyJob::$lastContext);
        $this->assertEquals($workspace->id, DummyJob::$lastContext->current->id);
        $this->assertEquals('queue', DummyJob::$lastContext->source);
    }
}
