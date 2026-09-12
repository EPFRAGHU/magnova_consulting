<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenantFromUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! tenancy()->initialized) {
            tenancy()->initialize($request->user()->tenant);
        }

        return $next($request);
    }
}
