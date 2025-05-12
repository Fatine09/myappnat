<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsClient
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->role !== 'client') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized. Client access required.'], 403);
            }
            return redirect()->route('login');
        }

        return $next($request);
    }
}