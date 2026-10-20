<?php

namespace ZaberDev\Context\Contracts;

interface ContextResolver
{
    /**
     * Attempt to resolve the active context.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolve(): ?\Illuminate\Database\Eloquent\Model;
}
