<?php

namespace ZaberDev\Context\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use ZaberDev\Context\Facades\Context;

class ClearContext
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
        $response = $next($request);
        
        // Ensure context is cleared at the end of the request
        // Very useful for Octane or long-running workers.
        Context::clear();

        return $response;
    }
}
