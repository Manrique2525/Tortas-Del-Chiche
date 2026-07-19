<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('admin_authenticated')) {
            return redirect()->route('admin.login');
        }

        $lastActivity = session('admin_last_activity');
        if ($lastActivity && (now()->timestamp - $lastActivity) > 28800) {
            session()->forget('admin_authenticated');
            session()->forget('admin_last_activity');
            return redirect()->route('admin.login');
        }

        session(['admin_last_activity' => now()->timestamp]);

        return $next($request);
    }
}
