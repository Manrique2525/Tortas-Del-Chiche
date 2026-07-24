<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('admin_authenticated')) {
            return redirect()->route('admin.login');
        }

        $userId = session('admin_user_id');
        if (!$userId || !User::where('id', $userId)->where('is_admin', true)->exists()) {
            session()->invalidate();
            return redirect()->route('admin.login');
        }

        $lastActivity = session('admin_last_activity');
        if ($lastActivity && (now()->timestamp - $lastActivity) > 1800) {
            session()->invalidate();
            return redirect()->route('admin.login');
        }

        session(['admin_last_activity' => now()->timestamp]);

        return $next($request);
    }
}
