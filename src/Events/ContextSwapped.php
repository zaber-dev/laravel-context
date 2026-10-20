<?php

namespace ZaberDev\Context\Events;

use Illuminate\Database\Eloquent\Model;

class ContextSwapped
{
    public function __construct(
        public readonly Model $new,
        public readonly ?Model $old
    ) {}
}
