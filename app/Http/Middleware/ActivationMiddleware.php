<?php

namespace App\Http\Middleware;

use Closure;

class ActivationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(\Auth::user()->status!=1){
            return response('Not Authorized ',401);
        }
        return $next($request);
    }
}
