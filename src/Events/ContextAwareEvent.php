<?php

namespace ZaberDev\Context\Events;

use ZaberDev\Context\DTO\ContextInfo;
use ZaberDev\Context\Facades\Context;

trait ContextAwareEvent
{
    /**
     * The context information when the event was dispatched.
     *
     * @var \ZaberDev\Context\DTO\ContextInfo|null
     */
    public ?ContextInfo $contextInfo = null;

    /**
     * Set the context information automatically upon instantiation.
     */
    protected function setupContextInfo(): void
    {
        $this->contextInfo = Context::info();
    }
}
