<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTenantSubscriptionIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user('tenant');
        $company = $user?->company;

        if (! $user || ! $company || ! $company->hasValidSubscription()) {
            return response()->json([
                'message' => 'Your company subscription is inactive or expired. Please contact support or renew the subscription.',
            ], 403);
        }

        return $next($request);
    }
}