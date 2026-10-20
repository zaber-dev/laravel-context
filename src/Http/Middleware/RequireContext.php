<?php

namespace ZaberDev\Context\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use ZaberDev\Context\Facades\Context;

class RequireContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // require() will throw ContextMissingException if no context is active
        Context::require();

        return $next($request);
    }
}
