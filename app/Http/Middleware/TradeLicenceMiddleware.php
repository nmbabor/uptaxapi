<?php

namespace App\Http\Middleware;

use App\Models\UserPermission;
use Closure;

class TradeLicenceMiddleware
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
        $permissions = UserPermission::where('user_id',\Auth::user()->id)->pluck('permission')->toArray();
        if(in_array('trade-licence',$permissions)) {
            return $next($request);
        }else{
            return response('Not Authorized ',401);
        }

    }
}
