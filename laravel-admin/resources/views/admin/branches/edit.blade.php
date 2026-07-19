<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Editar Sucursal - Admin</title>
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
        .admin-header-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .admin-header-left img { width: 42px; height: 42px; border-radius: 50%; border: 2px solid #FF6B35; flex-shrink: 0; }
        .admin-header-left h1 { color: #FFD700; font-size: 1rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .admin-header-left p { color: #aaa; font-size: 0.7rem; }
        .header-actions { display: flex; gap: 8px; align-items: center; flex-shrink: 0; }
        .back-btn {
            background: transparent; color: #aaa; border: 2px solid #555;
            padding: 7px 14px; border-radius: 8px; font-family: 'Poppins', sans-serif;
            font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;
            text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
            white-space: nowrap;
        }
        .back-btn:hover { border-color: #FF6B35; color: #FF6B35; }
        .logout-btn {
            background: transparent; color: #ff6b6b; border: 2px solid #ff6b6b;
            padding: 7px 14px; border-radius: 8px; font-family: 'Poppins', sans-serif;
            font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;
            white-space: nowrap;
        }
        .logout-btn:hover { background: #ff6b6b; color: white; }
        .btn-label { display: inline; }

        .content { max-width: 780px; margin: 30px auto; padding: 0 20px; }

        .card {
            background: white; border-radius: 16px; padding: 28px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 20px;
        }
        .card h2 {
            font-size: 0.95rem; color: #1a1a1a; margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
        }
        .card h2 i { color: #FF6B35; }

        .form-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 14px;
        }
        .form-grid .full { grid-column: 1 / -1; }
        .form-group label {
            display: block; font-size: 0.7rem; font-weight: 600; color: #555;
            margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .form-group input, .form-group textarea {
            width: 100%; padding: 9px 12px; border: 2px solid #e0e0e0; border-radius: 10px;
            font-family: 'Poppins', sans-serif; font-size: 0.85rem; transition: border-color 0.3s;
        }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #FF6B35; }
        .form-group textarea { resize: vertical; min-height: 60px; }

        .schedule-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px;
            margin-top: 6px;
        }
        .schedule-day {
            background: #fafafa; border: 2px solid #e0e0e0; border-radius: 10px; padding: 10px 12px;
        }
        .schedule-day-label {
            font-size: 0.7rem; font-weight: 700; color: #1a1a1a; margin-bottom: 6px;
            text-transform: capitalize; text-align: center;
        }
        .schedule-day-inputs {
            display: flex; gap: 6px; align-items: center;
        }
        .schedule-day-inputs input {
            flex: 1; padding: 5px 6px; border: 1px solid #ddd; border-radius: 6px;
            font-family: 'Poppins', sans-serif; font-size: 0.75rem; text-align: center;
        }
        .schedule-day-inputs span { font-size: 0.65rem; color: #888; }

        .btn {
            padding: 11px 24px; border: none; border-radius: 10px;
            font-family: 'Poppins', sans-serif; font-size: 0.85rem; font-weight: 700;
            cursor: pointer; transition: all 0.3s ease; display: inline-flex;
            align-items: center; gap: 6px;
        }
        .btn-primary { background: linear-gradient(135deg, #FF6B35, #e55a2d); color: white; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(255,107,53,0.4); }
        .btn-secondary { background: #e0e0e0; color: #555; }
        .btn-secondary:hover { background: #d0d0d0; }
        .form-actions { display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end; }

        .hint { font-size: 0.65rem; color: #999; margin-top: 4px; }

        .toggle-row {
            display: flex; align-items: center; gap: 10px; padding: 8px 0;
        }
        .toggle-row input[type="checkbox"] {
            width: 18px; height: 18px; accent-color: #27ae60; cursor: pointer;
        }

        @media (max-width: 768px) {
            .admin-header { padding: 14px 16px; }
            .back-btn, .logout-btn { padding: 7px 10px; font-size: 0.7rem; }
            .btn-label { display: none; }
            .content { padding: 0 16px; margin: 24px auto; }
            .form-grid { grid-template-columns: 1fr; }
            .schedule-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); }
        }
        @media (max-width: 576px) {
            .admin-header { padding: 12px 14px; flex-wrap: wrap; gap: 10px; }
            .admin-header-left { flex: 1; min-width: 0; }
            .admin-header-left img { width: 36px; height: 36px; }
            .admin-header-left h1 { font-size: 0.88rem; }
            .header-actions { width: 100%; justify-content: flex-end; }
            .content { padding: 0 14px; margin: 20px auto; }
            .card { padding: 20px; border-radius: 14px; }
            .card h2 { font-size: 0.88rem; }
            .schedule-grid { grid-template-columns: 1fr 1fr; }
            .form-actions { flex-direction: column; }
            .form-actions .btn { width: 100%; justify-content: center; }
        }
        @media (max-width: 420px) {
            .admin-header { padding: 10px 12px; }
            .admin-header-left img { width: 32px; height: 32px; }
            .admin-header-left h1 { font-size: 0.8rem; }
            .admin-header-left p { font-size: 0.6rem; }
            .content { padding: 0 12px; margin: 16px auto; }
            .card { padding: 16px; border-radius: 12px; }
            .schedule-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="admin-header-left">
            <img src="/img/logo.jpeg" alt="Logo">
            <div>
                <h1>Editar Sucursal</h1>
                <p>{{ $branch->name }}</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.branches') }}" class="back-btn"><i class="fas fa-arrow-left"></i> <span class="btn-label">Sucursales</span></a>
            <form method="POST" action="{{ route('admin.logout') }}" style="display:inline">
                @csrf
                <button class="logout-btn" type="submit"><i class="fas fa-sign-out-alt"></i> <span class="btn-label">Salir</span></button>
            </form>
        </div>
    </div>

    <div class="content">
        <form method="POST" action="{{ route('admin.branches.update', $branch) }}">
            @csrf
            @method('PUT')

            <div class="card">
                <h2><i class="fas fa-info-circle"></i> Información general</h2>
                <div class="form-grid">
                    <div class="full form-group">
                        <label>Nombre *</label>
                        <input type="text" name="name" placeholder="Ej: Sucursal Centro" required maxlength="255"
                               value="{{ old('name', $branch->name) }}">
                        @error('name') <p style="color:#e74c3c;font-size:0.65rem;margin-top:4px">{{ $message }}</p> @enderror
                    </div>
                    <div class="full form-group">
                        <label>Dirección</label>
                        <textarea name="address" placeholder="Dirección completa de la sucursal">{{ old('address', $branch->address) }}</textarea>
                        @error('address') <p style="color:#e74c3c;font-size:0.65rem;margin-top:4px">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="phone" placeholder="993 309 2124" value="{{ old('phone', $branch->phone) }}">
                        @error('phone') <p style="color:#e74c3c;font-size:0.65rem;margin-top:4px">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label>WhatsApp</label>
                        <input type="text" name="whatsapp" placeholder="529933092124" value="{{ old('whatsapp', $branch->whatsapp) }}">
                        @error('whatsapp') <p style="color:#e74c3c;font-size:0.65rem;margin-top:4px">{{ $message }}</p> @enderror
                        <p class="hint">Con código de país (52).</p>
                    </div>
                    <div class="form-group">
                        <label>Latitud</label>
                        <input type="number" step="any" name="latitude" placeholder="17.9865" value="{{ old('latitude', $branch->latitude) }}">
                        @error('latitude') <p style="color:#e74c3c;font-size:0.65rem;margin-top:4px">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label>Longitud</label>
                        <input type="number" step="any" name="longitude" placeholder="-92.9531" value="{{ old('longitude', $branch->longitude) }}">
                        @error('longitude') <p style="color:#e74c3c;font-size:0.65rem;margin-top:4px">{{ $message }}</p> @enderror
                    </div>
                    <div class="full form-group">
                        <label>Texto de horario (visible)</label>
                        <input type="text" name="schedule_text" placeholder="Lunes a Domingo de 7:00 am a 2:00 pm" value="{{ old('schedule_text', $branch->schedule_text) }}">
                    </div>
                    <div class="full form-group">
                        <label>URL Didi Food</label>
                        <input type="text" name="didi_url" placeholder="https://www.didi-food.com/..." value="{{ old('didi_url', $branch->didi_url) }}">
                    </div>
                    <div class="form-group">
                        <label>Orden</label>
                        <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $branch->sort_order) }}">
                    </div>
                    <div class="form-group">
                        <div class="toggle-row">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $branch->is_active ? '1' : '0') === '1' ? 'checked' : '' }}>
                            <label style="margin:0;text-transform:none;font-size:0.85rem;font-weight:600;">Sucursal activa</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2><i class="fas fa-clock"></i> Horario por día</h2>
                <p style="font-size:0.75rem;color:#888;margin-bottom:14px;">Configura el horario de apertura y cierre para cada día de la semana.</p>
                @php
                    $days = [
                        'monday' => 'Lunes',
                        'tuesday' => 'Martes',
                        'wednesday' => 'Miércoles',
                        'thursday' => 'Jueves',
                        'friday' => 'Viernes',
                        'saturday' => 'Sábado',
                        'sunday' => 'Domingo',
                    ];
                    $schedule = $branch->schedule;
                @endphp
                <div class="schedule-grid">
                    @foreach($days as $dayKey => $dayLabel)
                        @php
                            $open = $schedule[$dayKey]['open'] ?? '07:00';
                            $close = $schedule[$dayKey]['close'] ?? '14:00';
                        @endphp
                        <div class="schedule-day">
                            <div class="schedule-day-label">{{ $dayLabel }}</div>
                            <div class="schedule-day-inputs">
                                <input type="time" name="{{ $dayKey }}_open" value="{{ old($dayKey . '_open', $open) }}">
                                <span>a</span>
                                <input type="time" name="{{ $dayKey }}_close" value="{{ old($dayKey . '_close', $close) }}">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.branches') }}" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar cambios</button>
            </div>
        </form>
    </div>
    <script src="/js/admin-notify.js"></script>
</body>
</html>
