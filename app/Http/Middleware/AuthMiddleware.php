<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Session;

class AuthMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!Session::has('user')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
