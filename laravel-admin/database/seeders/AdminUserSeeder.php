<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('app.admin_email') ?: 'admin@lastortasdelchiche.com';

        User::updateOrCreate(
            ['email' => $email],
            [
                'name'     => 'Administrador',
                'password' => config('app.admin_password') ?: 'admin',
                'is_admin' => true,
            ]
        );
    }
}
