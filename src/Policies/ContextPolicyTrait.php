<?php

namespace ZaberDev\Context\Policies;

use Illuminate\Database\Eloquent\Model;
use ZaberDev\Context\Facades\Context;

trait ContextPolicyTrait
{
    /**
     * Determine if the model belongs to the current active context.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    protected function matchesCurrentContext(Model $model): bool
    {
        $context = Context::current();

        if (! $context) {
            return true;
        }

        return $model->getAttribute('context_type') === $context->getMorphClass() &&
               $model->getAttribute('context_id') === $context->getKey();
    }
}
