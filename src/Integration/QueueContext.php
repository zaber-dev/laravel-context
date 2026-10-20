<?php

namespace ZaberDev\Context\Integration;

use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobProcessing;
use ZaberDev\Context\Facades\Context;
use Illuminate\Database\Eloquent\Relations\Relation;

class QueueContext
{
    public static function register(): void
    {
        // Serialize context when pushing a job
        Queue::createPayloadUsing(function () {
            $stack = Context::all();
            
            if (empty($stack)) {
                return [];
            }
            
            $serializedStack = array_map(function ($contextModel) {
                return [
                    'class' => $contextModel->getMorphClass(),
                    'id' => $contextModel->getKey(),
                ];
            }, $stack);
            
            return ['context_stack' => $serializedStack];
        });

        // Restore context when processing a job
        Queue::before(function (JobProcessing $event) {
            $payload = $event->job->payload();
            
            Context::clear(); // start fresh

            if (isset($payload['context_stack']) && is_array($payload['context_stack'])) {
                foreach ($payload['context_stack'] as $contextData) {
                    if (isset($contextData['class'], $contextData['id'])) {
                        $class = Relation::getMorphedModel($contextData['class']) ?? $contextData['class'];
                        
                        if (class_exists($class)) {
                            // Fetch the model
                            $model = $class::find($contextData['id']);
                            if ($model) {
                                Context::push($model, 'queue');
                            }
                        }
                    }
                }
            }
        });
    }
}
