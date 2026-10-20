<?php

namespace ZaberDev\Context;

use Illuminate\Support\ServiceProvider;
use ZaberDev\Context\Validation\ContextAwareRule;
use Illuminate\Validation\Rule;

class ContextServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('context.manager', function ($app) {
            return new ContextManager($app);
        });
    }

    public function boot(): void
    {
        $this->registerValidationMacros();
        $this->registerRouteMacros();
        
        \ZaberDev\Context\Integration\QueueContext::register();
        \ZaberDev\Context\Integration\CacheContext::register();
        \ZaberDev\Context\Integration\LogContext::register();
        \ZaberDev\Context\Integration\StorageContext::register();
    }

    protected function registerRouteMacros(): void
    {
        \Illuminate\Support\Facades\Route::macro('context', function (string $parameterName) {
            /** @var \Illuminate\Routing\Route $this */
            return $this->middleware(\ZaberDev\Context\Http\Middleware\SetContext::class . ':' . $parameterName);
        });
    }

    protected function registerValidationMacros(): void
    {
        Rule::macro('contextUnique', function ($table, $column = 'NULL') {
            return ContextAwareRule::unique($table, $column);
        });

        Rule::macro('contextExists', function ($table, $column = 'NULL') {
            return ContextAwareRule::exists($table, $column);
        });
    }
}
