<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function show()
    {
        $user = null;
        if (session('admin_user_id')) {
            $user = User::find(session('admin_user_id'));
        }

        return view('admin.profile', compact('user'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:8|confirmed',
        ]);

        $userId = session('admin_user_id');
        if (!$userId) {
            return back()->withErrors(['current_password' => 'No tienes un usuario asociado. Usa el login con email.']);
        }

        $user = User::find($userId);
        if (!$user) {
            return back()->withErrors(['current_password' => 'Usuario no encontrado.']);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        Log::info('Admin cambió su contraseña', ['user_id' => $userId]);
        return back()->with('success', 'Contraseña actualizada correctamente.');
    }

    public function updateEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255|unique:users,email,' . session('admin_user_id'),
        ]);

        $userId = session('admin_user_id');
        if (!$userId) {
            return back()->withErrors(['email' => 'No tienes un usuario asociado. Usa el login con email.']);
        }

        $user = User::find($userId);
        if (!$user) {
            return back()->withErrors(['email' => 'Usuario no encontrado.']);
        }

        $user->email = $request->email;
        $user->save();

        Log::info('Admin cambió su email', ['user_id' => $userId]);
        return back()->with('success', 'Email actualizado correctamente.');
    }
}
