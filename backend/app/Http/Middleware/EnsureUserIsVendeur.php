<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsVendeur
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->role !== 'vendeur') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized. Vendor access required.'], 403);
            }
            return redirect()->route('login');
        }

        return $next($request);
    }
}