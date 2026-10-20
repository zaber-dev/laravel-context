<?php

namespace ZaberDev\Context\Validation;

use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Rules\Exists;
use ZaberDev\Context\Facades\Context;

class ContextAwareRule
{
    /**
     * Create a context-aware unique rule.
     *
     * @param  string  $table
     * @param  string  $column
     * @return \Illuminate\Validation\Rules\Unique
     */
    public static function unique(string $table, string $column = 'NULL'): Unique
    {
        $rule = new Unique($table, $column);

        if ($context = Context::current()) {
            $rule->where('context_type', $context->getMorphClass())
                 ->where('context_id', $context->getKey());
        }

        return $rule;
    }

    /**
     * Create a context-aware exists rule.
     *
     * @param  string  $table
     * @param  string  $column
     * @return \Illuminate\Validation\Rules\Exists
     */
    public static function exists(string $table, string $column = 'NULL'): Exists
    {
        $rule = new Exists($table, $column);

        if ($context = Context::current()) {
            $rule->where('context_type', $context->getMorphClass())
                 ->where('context_id', $context->getKey());
        }

        return $rule;
    }
}
