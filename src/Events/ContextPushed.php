<?php

namespace ZaberDev\Context\Events;

use Illuminate\Database\Eloquent\Model;

class ContextPushed
{
    public function __construct(
        public readonly Model $context,
        public readonly string $source
    ) {}
}
