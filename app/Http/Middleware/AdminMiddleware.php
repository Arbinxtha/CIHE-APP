<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // return response()->json(Auth::check());
        if (auth()->check() && auth()->user()->role === 'admin') {
            return $next($request);
        }
        return response()->json(['error' => 'Unauthorized'], 403);
    }
}
