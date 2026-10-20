<?php

namespace ZaberDev\Context\Factories;

use Illuminate\Database\Eloquent\Model;

trait ContextFactoryTrait
{
    /**
     * Scope the factory to a specific context.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $context
     * @return static
     */
    public function forContext(Model $context)
    {
        return $this->state(function (array $attributes) use ($context) {
            return [
                'context_type' => $context->getMorphClass(),
                'context_id' => $context->getKey(),
            ];
        });
    }
}
