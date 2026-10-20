<?php

namespace ZaberDev\Context\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasContextualModels
{
    /**
     * Define a polymorphic one-to-many context relationship.
     *
     * @param  string  $related
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function contextual(string $related): MorphMany
    {
        return $this->morphMany($related, 'context');
    }
}
