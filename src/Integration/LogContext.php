<?php

namespace ZaberDev\Context\Integration;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use ZaberDev\Context\Events\ContextPushed;
use ZaberDev\Context\Events\ContextPopped;
use ZaberDev\Context\Events\ContextSwapped;
use ZaberDev\Context\Facades\Context;

class LogContext
{
    public static function register(): void
    {
        Event::listen(ContextPushed::class, function (ContextPushed $event) {
            Log::withContext([
                'context_type' => $event->context->getMorphClass(),
                'context_id' => $event->context->getKey(),
            ]);
        });
        
        Event::listen(ContextPopped::class, function () {
            $current = Context::current();
            if ($current) {
                Log::withContext([
                    'context_type' => $current->getMorphClass(),
                    'context_id' => $current->getKey(),
                ]);
            } else {
                if (method_exists(Log::class, 'withoutContext')) {
                    Log::withoutContext();
                } else {
                    // Fallback for older Laravel
                    Log::withContext([
                        'context_type' => null,
                        'context_id' => null,
                    ]);
                }
            }
        });
        
        Event::listen(ContextSwapped::class, function (ContextSwapped $event) {
            Log::withContext([
                'context_type' => $event->new->getMorphClass(),
                'context_id' => $event->new->getKey(),
            ]);
        });
    }
}
