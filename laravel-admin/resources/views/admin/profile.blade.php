<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Perfil - Admin - Las Tortas Del Chiche</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f5f5f5; min-height: 100vh; }

        .admin-header {
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
            padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3); position: sticky; top: 0; z-index: 100;
        }
        .admin-header-left { display: flex; align-items: center; gap: 12px; }
        .admin-header-left img { width: 42px; height: 42px; border-radius: 50%; border: 2px solid #FF6B35; }
        .admin-header-left h1 { color: #FFD700; font-size: 1rem; font-weight: 700; }
        .admin-header-left p { color: #aaa; font-size: 0.7rem; }
        .header-actions { display: flex; gap: 8px; align-items: center; }
        .back-btn {
            background: transparent; color: #aaa; border: 2px solid #555;
            padding: 7px 14px; border-radius: 8px; font-family: 'Poppins', sans-serif;
            font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;
            text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
        }
        .back-btn:hover { color: #FFD700; border-color: #FFD700; }

        .container { max-width: 600px; margin: 30px auto; padding: 0 20px; }

        .card {
            background: white; border-radius: 16px; padding: 28px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 20px;
        }
        .card h2 { font-size: 1.1rem; font-weight: 700; color: #1a1a1a; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .card h2 i { color: #FF6B35; }

        .info-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px 0; border-bottom: 1px solid #f0f0f0;
        }
        .info-row:last-child { border-bottom: none; }
        .info-row .label { color: #888; font-size: 0.85rem; }
        .info-row .value { color: #1a1a1a; font-weight: 600; font-size: 0.9rem; }

        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; color: #555; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; }
        .form-group input {
            width: 100%; padding: 12px 14px; border: 2px solid #e0e0e0; border-radius: 10px;
            font-size: 0.9rem; font-family: 'Poppins', sans-serif; transition: border-color 0.3s ease; background: #f9f9f9;
        }
        .form-group input:focus { outline: none; border-color: #FF6B35; background: #fff; }

        .btn {
            padding: 10px 20px; border-radius: 10px; font-family: 'Poppins', sans-serif;
            font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;
            border: none; display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-primary { background: #FF6B35; color: white; }
        .btn-primary:hover { background: #e55a2b; transform: translateY(-1px); }

        .alert {
            padding: 12px 16px; border-radius: 10px; font-size: 0.85rem; margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
        }
        .alert-success { background: #e8f5e9; color: #2e7d32; }
        .alert-error { background: #ffebee; color: #c62828; }

        .note { color: #999; font-size: 0.8rem; margin-top: 4px; }

        hr { border: none; border-top: 1px solid #eee; margin: 20px 0; }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-header-left">
            <img src="/img/logo.jpeg" alt="">
            <div>
                <h1>Las Tortas Del Chiche</h1>
                <p>Perfil</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.dashboard') }}" class="back-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>
        </div>
    </header>

    <div class="container">
        @if (session('success'))
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
        @endif

        @if ($errors->any())
            @foreach ($errors->all() as $error)
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ $error }}</div>
            @endforeach
        @endif

        @if ($user)
            <div class="card">
                <h2><i class="fas fa-user"></i> Información del perfil</h2>
                <div class="info-row">
                    <span class="label">Nombre</span>
                    <span class="value">{{ $user->name }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Email</span>
                    <span class="value">{{ $user->email }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Miembro desde</span>
                    <span class="value">{{ $user->created_at->format('d/m/Y') }}</span>
                </div>
            </div>

            <div class="card">
                <h2><i class="fas fa-envelope"></i> Cambiar email</h2>
                <form method="POST" action="{{ route('admin.profile.email') }}">
                    @csrf
                    <div class="form-group">
                        <label for="email">Nuevo email</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar email</button>
                </form>
            </div>

            <div class="card">
                <h2><i class="fas fa-lock"></i> Cambiar contraseña</h2>
                <form method="POST" action="{{ route('admin.profile.password') }}">
                    @csrf
                    <div class="form-group">
                        <label for="current_password">Contraseña actual</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">Nueva contraseña</label>
                        <input type="password" id="new_password" name="new_password" required minlength="8">
                        <div class="note">Mínimo 8 caracteres</div>
                    </div>
                    <div class="form-group">
                        <label for="new_password_confirmation">Confirmar nueva contraseña</label>
                        <input type="password" id="new_password_confirmation" name="new_password_confirmation" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Cambiar contraseña</button>
                </form>
            </div>
        @else
            <div class="card">
                <h2><i class="fas fa-info-circle"></i> Sin usuario asociado</h2>
                <p style="color:#888;font-size:0.9rem;">
                    Iniciaste sesión con el <strong>password único</strong> (sin email).
                    <br><br>
                    Para usar el perfil y cambiar tu contraseña, inicia sesión con tu <strong>email y contraseña</strong> desde la página de login.
                    <br><br>
                    <a href="{{ route('admin.login') }}" style="color:#FF6B35;">Ir al login</a>
                </p>
            </div>
        @endif
    </div>
</body>
</html>
