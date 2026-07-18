<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cupones - Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f5f5f5; min-height: 100vh; }

        .admin-header {
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
            padding: 20px 24px; display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3); position: sticky; top: 0; z-index: 100;
        }
        .admin-header-left { display: flex; align-items: center; gap: 14px; }
        .admin-header-left img { width: 45px; height: 45px; border-radius: 50%; border: 2px solid #FF6B35; }
        .admin-header-left h1 { color: #FFD700; font-size: 1.1rem; font-weight: 700; }
        .admin-header-left p { color: #aaa; font-size: 0.75rem; }
        .header-actions { display: flex; gap: 10px; align-items: center; }
        .back-btn {
            background: transparent; color: #aaa; border: 2px solid #555;
            padding: 8px 16px; border-radius: 8px; font-family: 'Poppins', sans-serif;
            font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;
            text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
        }
        .back-btn:hover { border-color: #FF6B35; color: #FF6B35; }
        .logout-btn {
            background: transparent; color: #ff6b6b; border: 2px solid #ff6b6b;
            padding: 8px 16px; border-radius: 8px; font-family: 'Poppins', sans-serif;
            font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;
        }
        .logout-btn:hover { background: #ff6b6b; color: white; }

        .content { max-width: 900px; margin: 30px auto; padding: 0 20px; }

        .success-msg {
            background: #d4edda; color: #155724; border: 1px solid #c3e6cb;
            padding: 12px 18px; border-radius: 10px; margin-bottom: 20px;
            font-size: 0.85rem; display: flex; align-items: center; gap: 8px;
        }

        .card {
            background: white; border-radius: 16px; padding: 28px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 24px;
        }
        .card h2 {
            font-size: 1rem; color: #1a1a1a; margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
        }
        .card h2 i { color: #FF6B35; }

        .form-row {
            display: grid; grid-template-columns: 1fr 140px auto; gap: 12px; align-items: end;
        }
        .form-group label {
            display: block; font-size: 0.75rem; font-weight: 600; color: #555;
            margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .form-group input {
            width: 100%; padding: 10px 14px; border: 2px solid #e0e0e0; border-radius: 10px;
            font-family: 'Poppins', sans-serif; font-size: 0.9rem; transition: border-color 0.3s;
        }
        .form-group input:focus { outline: none; border-color: #FF6B35; }

        .btn {
            padding: 10px 20px; border: none; border-radius: 10px;
            font-family: 'Poppins', sans-serif; font-size: 0.85rem; font-weight: 600;
            cursor: pointer; transition: all 0.3s ease; display: inline-flex;
            align-items: center; gap: 6px;
        }
        .btn-primary { background: linear-gradient(135deg, #FF6B35, #e55a2d); color: white; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(255,107,53,0.4); }
        .btn-sm { padding: 6px 12px; font-size: 0.75rem; }
        .btn-danger { background: #fee; color: #e74c3c; border: 1px solid #fcc; }
        .btn-danger:hover { background: #e74c3c; color: white; }
        .btn-toggle { padding: 6px 14px; font-size: 0.75rem; }
        .btn-toggle.active { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .btn-toggle.inactive { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .coupon-list { list-style: none; }
        .coupon-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 0; border-bottom: 1px solid #f0f0f0;
        }
        .coupon-item:last-child { border-bottom: none; }
        .coupon-info { flex: 1; }
        .coupon-code {
            font-size: 1rem; font-weight: 700; color: #1a1a1a;
            font-family: 'Courier New', monospace; letter-spacing: 1px;
        }
        .coupon-detail {
            font-size: 0.75rem; color: #888; margin-top: 2px;
        }
        .coupon-status {
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            font-size: 0.7rem; font-weight: 700; margin-left: 8px;
        }
        .coupon-status.active { background: #d4edda; color: #155724; }
        .coupon-status.inactive { background: #f8d7da; color: #721c24; }
        .coupon-actions { display: flex; gap: 8px; align-items: center; }

        .empty-state {
            text-align: center; padding: 40px 20px; color: #aaa;
        }
        .empty-state i { font-size: 2.5rem; margin-bottom: 12px; display: block; }
        .empty-state p { font-size: 0.85rem; }

        .hint {
            font-size: 0.7rem; color: #999; margin-top: 6px;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="admin-header-left">
            <img src="/img/logo.jpeg" alt="Logo">
            <div>
                <h1>Cupones de Descuento</h1>
                <p>Gestiona los códigos de descuento del carrito</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.dashboard') }}" class="back-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <form method="POST" action="{{ route('admin.logout') }}" style="display:inline">
                @csrf
                <button class="logout-btn" type="submit"><i class="fas fa-sign-out-alt"></i></button>
            </form>
        </div>
    </div>

    <div class="content">
        @if(session('success'))
            <div class="success-msg"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
        @endif

        <div class="card">
            <h2><i class="fas fa-plus-circle"></i> Crear cupón</h2>
            <form method="POST" action="{{ route('admin.coupons.store') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label>Código</label>
                        <input type="text" name="code" placeholder="EJ: VERANO20" required maxlength="50"
                               value="{{ old('code') }}" style="text-transform: uppercase;">
                        @error('code') <p style="color:#e74c3c;font-size:0.7rem;margin-top:4px">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label>Descuento %</label>
                        <input type="number" name="discount_percent" min="1" max="100" required
                               value="{{ old('discount_percent', '10') }}" step="0.01">
                        @error('discount_percent') <p style="color:#e74c3c;font-size:0.7rem;margin-top:4px">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Crear</button>
                </div>
                <p class="hint">El código se guarda en mayúsculas. El cliente lo escribe en el carrito para obtener el descuento.</p>
            </form>
        </div>

        <div class="card">
            <h2><i class="fas fa-tags"></i> Cupones existentes</h2>
            @if($coupons->isEmpty())
                <div class="empty-state">
                    <i class="fas fa-ticket-alt"></i>
                    <p>No hay cupones creados todavía</p>
                </div>
            @else
                <ul class="coupon-list">
                    @foreach($coupons as $coupon)
                        <li class="coupon-item">
                            <div class="coupon-info">
                                <span class="coupon-code">{{ $coupon->code }}</span>
                                <span class="coupon-status {{ $coupon->active ? 'active' : 'inactive' }}">
                                    {{ $coupon->active ? 'Activo' : 'Inactivo' }}
                                </span>
                                <div class="coupon-descuento">{{ $coupon->discount_percent }}% de descuento</div>
                                <div class="coupon-detail">Creado: {{ $coupon->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                            <div class="coupon-actions">
                                <button class="btn btn-toggle {{ $coupon->active ? 'active' : 'inactive' }}"
                                        onclick="toggleCoupon({{ $coupon->id }}, this)">
                                    <i class="fas {{ $coupon->active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                    {{ $coupon->active ? 'Activo' : 'Inactivo' }}
                                </button>
                                <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}"
                                      onsubmit="return confirm('¿Eliminar este cupón permanentemente?')" style="display:inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <script>
        async function toggleCoupon(id, btn) {
            const res = await fetch(`/admin/coupons/${id}/toggle`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            });
            const data = await res.json();
            if (data.success) {
                btn.className = 'btn btn-toggle ' + (data.active ? 'active' : 'inactive');
                btn.innerHTML = `<i class="fas ${data.active ? 'fa-toggle-on' : 'fa-toggle-off'}"></i> ${data.active ? 'Activo' : 'Inactivo'}`;
                const statusBadge = btn.closest('.coupon-item').querySelector('.coupon-status');
                statusBadge.className = 'coupon-status ' + (data.active ? 'active' : 'inactive');
                statusBadge.textContent = data.active ? 'Activo' : 'Inactivo';
            }
        }
    </script>
    <script src="/js/admin-notify.js"></script>
</body>
</html>
