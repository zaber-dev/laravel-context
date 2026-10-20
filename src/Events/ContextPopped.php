<?php

namespace ZaberDev\Context\Events;

use Illuminate\Database\Eloquent\Model;

class ContextPopped
{
    public function __construct(
        public readonly Model $context
    ) {}
}
