<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('admin_authenticated')) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'email'    => 'required|email',
        ]);

        $user = User::where('email', $request->email)->where('is_admin', true)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            session()->regenerate(true);
            session([
                'admin_authenticated' => true,
                'admin_user_id'       => $user->id,
                'admin_user_name'     => $user->name,
                'admin_last_activity' => now()->timestamp,
            ]);
            Log::info('Admin login exitoso', ['email' => $request->email]);
            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors([
            'password' => 'Credenciales incorrectas.',
        ])->withInput($request->only('email'));
    }

    public function logout()
    {
        session()->forget('admin_authenticated');
        session()->forget('admin_user_id');
        session()->forget('admin_user_name');
        session()->forget('admin_last_activity');
        return redirect()->route('admin.login');
    }
}
