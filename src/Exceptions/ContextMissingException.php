<?php

namespace ZaberDev\Context\Exceptions;

use RuntimeException;

class ContextMissingException extends RuntimeException
{
    public function __construct(string $message = 'Execution context is required but none was found.')
    {
        parent::__construct($message);
    }
}
