<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SetTenantGuard
{
    public function handle(Request $request, Closure $next)
    {
        Auth::shouldUse('tenant');

        return $next($request);
    }
}