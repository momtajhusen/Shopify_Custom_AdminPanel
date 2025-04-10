<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AdminOrVendor
{
    public function handle($request, Closure $next)
    {
        if (Auth::check() || Auth::guard('vendor')->check()) {
            return $next($request);
        }

        return redirect()->route('admin.login')->withErrors(['Access denied.']);
    }
}
