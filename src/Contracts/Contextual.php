<?php

namespace ZaberDev\Context\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphTo;

interface Contextual
{
    /**
     * Get the context that owns this model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function context(): MorphTo;
}
