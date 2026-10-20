<?php

namespace ZaberDev\Context\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use ZaberDev\Context\Facades\Context;

trait IsContextual
{
    /**
     * Boot the contextual trait for a model.
     *
     * @return void
     */
    public static function bootIsContextual()
    {
        // Add global scope to automatically filter by the active context
        static::addGlobalScope('context', function (Builder $builder) {
            $context = Context::current();
            if ($context) {
                $builder->where('context_id', $context->getKey())
                        ->where('context_type', $context->getMorphClass());
            }
        });

        // Automatically assign the context when creating the model
        static::creating(function (Model $model) {
            $context = Context::current();
            
            if ($context && ! $model->getAttribute('context_id')) {
                $model->setAttribute('context_id', $context->getKey());
                $model->setAttribute('context_type', $context->getMorphClass());
            }
        });
    }

    /**
     * Scope a query to only include models within a specific context temporarily.
     */
    public function scopeWithin(Builder $query, Model $context): void
    {
        $query->withoutGlobalScope('context')
              ->where('context_id', $context->getKey())
              ->where('context_type', $context->getMorphClass());
    }

    /**
     * Scope a query to ignore the active context.
     */
    public function scopeWithoutContext(Builder $query): void
    {
        $query->withoutGlobalScope('context');
    }

    /**
     * Get the owning context model.
     */
    public function context()
    {
        return $this->morphTo();
    }
}
