<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Las Tortas Del Chiche</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-logo img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #FF6B35;
            box-shadow: 0 0 30px rgba(255, 107, 53, 0.3);
        }

        .login-logo h1 {
            color: #FFD700;
            font-size: 1.4rem;
            margin-top: 12px;
            font-weight: 700;
        }

        .login-logo p {
            color: #aaa;
            font-size: 0.85rem;
            margin-top: 4px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 35px 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .login-card h2 {
            text-align: center;
            color: #1a1a1a;
            font-size: 1.2rem;
            margin-bottom: 25px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #555;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: border-color 0.3s ease;
            background: #f9f9f9;
        }

        .form-group input:focus {
            outline: none;
            border-color: #FF6B35;
            background: #fff;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: #FF6B35;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 5px;
        }

        .login-btn:hover {
            background: #1a1a1a;
            color: #FFD700;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .error-msg {
            background: #ffebee;
            color: #c62828;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .error-msg::before {
            content: "\f06a";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #aaa;
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #FF6B35;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <img src="/img/logo.jpeg" alt="Las Tortas Del Chiche"
                 onerror="this.style.background='#FF6B35'; this.style.borderRadius='50%'; this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22120%22 height=%22120%22><rect width=%22120%22 height=%22120%22 fill=%22%23FF6B35%22 rx=%2260%22/><text x=%2260%22 y=%2275%22 text-anchor=%22middle%22 fill=%22white%22 font-size=%2240%22 font-family=%22sans-serif%22 font-weight=%22bold%22>TT</text></svg>'">
            <h1>Las Tortas Del Chiche</h1>
            <p>Panel de Administración</p>
        </div>

        <div class="login-card">
            <h2><i class="fas fa-lock"></i> Iniciar Sesión</h2>

            @if ($errors->any())
                <div class="error-msg">
                    {{ $errors->first('password') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.post') }}">
                @csrf
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-key"></i> Contraseña
                    </label>
                    <input type="password" id="password" name="password" placeholder="Escribe tu contraseña" required autofocus autocomplete="current-password">
                </div>
                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Entrar
                </button>
            </form>
        </div>

        <div class="back-link">
            <a href="/"><i class="fas fa-arrow-left"></i> Volver al sitio</a>
        </div>
    </div>
</body>
</html>
