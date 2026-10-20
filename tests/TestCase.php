<?php

namespace ZaberDev\Context\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use ZaberDev\Context\ContextServiceProvider;
use Illuminate\Database\Schema\Blueprint;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
    }

    protected function getPackageProviders($app)
    {
        return [
            ContextServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }

    protected function setUpDatabase($app)
    {
        $app['db']->connection()->getSchemaBuilder()->create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('context_type')->nullable();
            $table->unsignedBigInteger('context_id')->nullable();
            $table->string('title');
            $table->timestamps();
        });
    }
}
