<?php

namespace ZaberDev\Context\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use ZaberDev\Context\Facades\Context;
use Illuminate\Database\Eloquent\Model;

class SetContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $parameterName The name of the route parameter to use as context
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $parameterName)
    {
        $contextModel = $request->route($parameterName);

        if ($contextModel instanceof Model) {
            Context::set($contextModel);
        }

        return $next($request);
    }
}
