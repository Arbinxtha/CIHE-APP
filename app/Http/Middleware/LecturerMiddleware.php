<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LecturerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->role === 'lecturer') {
            return $next($request);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }
}
