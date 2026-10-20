<?php

namespace ZaberDev\Context\DTO;

use Illuminate\Database\Eloquent\Model;

class ContextInfo
{
    /**
     * @param  \Illuminate\Database\Eloquent\Model|null  $current  The active context model
     * @param  array<int, \Illuminate\Database\Eloquent\Model>  $stack  The full hierarchy of current contexts
     * @param  int  $depth  The depth of the nested context
     * @param  string|null  $source  Where this context was resolved from (e.g. 'manual', 'queue', 'request')
     * @param  \Illuminate\Database\Eloquent\Model|null  $parent  The parent context model (if any)
     * @param  \Illuminate\Database\Eloquent\Model|null  $root  The root context model (if any)
     * @param  string|null  $driver  The driver managing this context stack
     * @param  float|null  $startedAt  When the current context started
     * @param  array<int, \Illuminate\Database\Eloquent\Model>  $group  If the current context is a group of models
     */
    public function __construct(
        public readonly ?Model $current = null,
        public readonly array $stack = [],
        public readonly int $depth = 0,
        public readonly ?string $source = null,
        public readonly ?Model $parent = null,
        public readonly ?Model $root = null,
        public readonly ?string $driver = null,
        public readonly ?float $startedAt = null,
        public readonly ?string $boundaryId = null
    ) {}
}
