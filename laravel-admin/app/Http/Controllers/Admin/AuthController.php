<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
        ]);

        $adminPassword = config('app.admin_password');
        if (!$adminPassword) {
            return back()->withErrors([
                'password' => 'Error de configuración. Contacta al administrador.',
            ]);
        }

        if ($request->password === $adminPassword) {
            session()->regenerate(true);
            session(['admin_authenticated' => true]);
            session(['admin_last_activity' => now()->timestamp]);
            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors([
            'password' => 'Contraseña incorrecta.',
        ]);
    }

    public function logout()
    {
        session()->forget('admin_authenticated');
        return redirect()->route('admin.login');
    }
}
