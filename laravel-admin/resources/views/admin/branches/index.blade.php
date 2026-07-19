<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sucursales - Admin</title>
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

        .content { max-width: 960px; margin: 30px auto; padding: 0 20px; }

        .success-msg {
            background: #d4edda; color: #155724; border: 1px solid #c3e6cb;
            padding: 12px 16px; border-radius: 10px; margin-bottom: 20px;
            font-size: 0.8rem; display: flex; align-items: center; gap: 8px;
        }
        .error-msg {
            background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;
            padding: 12px 16px; border-radius: 10px; margin-bottom: 20px;
            font-size: 0.8rem; display: flex; align-items: center; gap: 8px;
        }

        .card {
            background: white; border-radius: 16px; padding: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 20px;
        }
        .card h2 {
            font-size: 0.95rem; color: #1a1a1a; margin-bottom: 18px;
            display: flex; align-items: center; gap: 8px;
        }
        .card h2 i { color: #FF6B35; }

        .btn {
            padding: 9px 18px; border: none; border-radius: 10px;
            font-family: 'Poppins', sans-serif; font-size: 0.8rem; font-weight: 600;
            cursor: pointer; transition: all 0.3s ease; display: inline-flex;
            align-items: center; gap: 6px; white-space: nowrap; text-decoration: none;
        }
        .btn-primary { background: linear-gradient(135deg, #FF6B35, #e55a2d); color: white; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(255,107,53,0.4); }
        .btn-sm { padding: 5px 10px; font-size: 0.7rem; }
        .btn-danger { background: #fee; color: #e74c3c; border: 1px solid #fcc; }
        .btn-danger:hover { background: #e74c3c; color: white; }
        .btn-outline {
            background: transparent; color: #FF6B35; border: 2px solid #FF6B35;
        }
        .btn-outline:hover { background: #FF6B35; color: white; }
        .btn-toggle { padding: 5px 12px; font-size: 0.7rem; }
        .btn-toggle.active { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .btn-toggle.inactive { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .branch-list { list-style: none; }
        .branch-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 0; border-bottom: 1px solid #f0f0f0; gap: 12px;
        }
        .branch-item:last-child { border-bottom: none; }
        .branch-info { flex: 1; min-width: 0; }
        .branch-name {
            font-size: 0.95rem; font-weight: 700; color: #1a1a1a;
            display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
        }
        .branch-detail {
            font-size: 0.7rem; color: #888; margin-top: 2px;
        }
        .branch-status {
            display: inline-block; padding: 2px 8px; border-radius: 20px;
            font-size: 0.65rem; font-weight: 700;
        }
        .branch-status.active { background: #d4edda; color: #155724; }
        .branch-status.inactive { background: #f8d7da; color: #721c24; }
        .branch-actions { display: flex; gap: 6px; align-items: center; flex-shrink: 0; }

        .empty-state {
            text-align: center; padding: 36px 20px; color: #aaa;
        }
        .empty-state i { font-size: 2.2rem; margin-bottom: 10px; display: block; }
        .empty-state p { font-size: 0.8rem; }

        .modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5); z-index: 200; display: none;
            align-items: center; justify-content: center; padding: 20px;
        }
        .modal-overlay.active { display: flex; }
        .modal-card {
            background: white; border-radius: 16px; padding: 28px; max-width: 400px; width: 100%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2); animation: modalIn 0.3s ease;
        }
        @keyframes modalIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        .modal-card h3 { font-size: 1rem; font-weight: 700; color: #1a1a1a; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
        .modal-card h3 i { color: #e53935; }
        .modal-card p { color: #666; font-size: 0.85rem; margin-bottom: 24px; }
        .modal-card .branch-to-delete { font-weight: 700; color: #1a1a1a; }
        .modal-actions { display: flex; gap: 12px; }
        .modal-actions .btn {
            flex: 1; padding: 12px; border: none; border-radius: 10px;
            font-family: 'Poppins', sans-serif; font-size: 0.85rem; font-weight: 700;
            cursor: pointer; transition: all 0.2s ease;
        }
        .btn-cancel { background: #e0e0e0; color: #555; }
        .btn-cancel:hover { background: #d0d0d0; }
        .btn-danger-modal { background: #e53935; color: white; }
        .btn-danger-modal:hover { background: #c62828; }

        @media (max-width: 768px) {
            .admin-header { padding: 14px 16px; }
            .back-btn, .logout-btn { padding: 7px 10px; font-size: 0.7rem; }
            .btn-label { display: none; }
            .content { padding: 0 16px; margin: 24px auto; }
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
            .branch-item { flex-wrap: wrap; gap: 10px; }
            .branch-actions { width: 100%; justify-content: flex-end; }
            .branch-name { font-size: 0.85rem; }
            .btn-primary { width: 100%; justify-content: center; }
        }
        @media (max-width: 420px) {
            .admin-header { padding: 10px 12px; }
            .admin-header-left img { width: 32px; height: 32px; }
            .admin-header-left h1 { font-size: 0.8rem; }
            .admin-header-left p { font-size: 0.6rem; }
            .back-btn, .logout-btn { padding: 6px 8px; font-size: 0.65rem; border-radius: 6px; }
            .content { padding: 0 12px; margin: 16px auto; }
            .card { padding: 16px; border-radius: 12px; }
            .branch-item { padding: 10px 0; }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="admin-header-left">
            <img src="/img/logo.jpeg" alt="Logo">
            <div>
                <h1>Sucursales</h1>
                <p>Gestiona las sucursales del negocio</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.dashboard') }}" class="back-btn"><i class="fas fa-arrow-left"></i> <span class="btn-label">Dashboard</span></a>
            <form method="POST" action="{{ route('admin.logout') }}" style="display:inline">
                @csrf
                <button class="logout-btn" type="submit"><i class="fas fa-sign-out-alt"></i> <span class="btn-label">Salir</span></button>
            </form>
        </div>
    </div>

    <div class="content">
        @if(session('success'))
            <div class="success-msg"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
        @endif

        <div class="card" style="text-align:right;padding:16px 24px;">
            <a href="{{ route('admin.branches.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva sucursal</a>
        </div>

        <div class="card">
            <h2><i class="fas fa-store"></i> Sucursales</h2>
            @if($branches->isEmpty())
                <div class="empty-state">
                    <i class="fas fa-store-alt"></i>
                    <p>No hay sucursales creadas todavía</p>
                </div>
            @else
                <ul class="branch-list">
                    @foreach($branches as $branch)
                        <li class="branch-item">
                            <div class="branch-info">
                                <div class="branch-name">
                                    {{ $branch->name }}
                                    <span class="branch-status {{ $branch->is_active ? 'active' : 'inactive' }}">
                                        {{ $branch->is_active ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </div>
                                <div class="branch-detail">{{ $branch->address ?: 'Sin dirección' }}</div>
                                <div class="branch-detail">{{ $branch->schedule_text ?: 'Horario no configurado' }}</div>
                            </div>
                            <div class="branch-actions">
                                <button class="btn btn-toggle {{ $branch->is_active ? 'active' : 'inactive' }}"
                                        onclick="toggleBranch({{ $branch->id }}, this)">
                                    <i class="fas {{ $branch->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                    {{ $branch->is_active ? 'Activa' : 'Inactiva' }}
                                </button>
                                <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-sm btn-outline" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-sm btn-danger" title="Eliminar"
                                        onclick="confirmDeleteBranch({{ $branch->id }}, '{{ addslashes($branch->name) }}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="modal-overlay" id="deleteModal">
        <div class="modal-card">
            <h3><i class="fas fa-exclamation-triangle"></i> Eliminar sucursal</h3>
            <p>¿Estás seguro de eliminar <span class="branch-to-delete" id="deleteBranchName"></span> permanentemente?</p>
            <form id="deleteForm" method="POST" style="display:none;">@csrf @method('DELETE')</form>
            <div class="modal-actions">
                <button class="btn btn-cancel" onclick="closeDeleteModal()">Cancelar</button>
                <button class="btn btn-danger-modal" onclick="submitDelete()">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
    </div>

    <script>
        function confirmDeleteBranch(id, name) {
            document.getElementById('deleteBranchName').textContent = name;
            document.getElementById('deleteForm').action = `/admin/branches/${id}`;
            document.getElementById('deleteModal').classList.add('active');
        }
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }
        function submitDelete() {
            document.getElementById('deleteForm').submit();
        }

        async function toggleBranch(id, btn) {
            const res = await fetch(`/admin/branches/${id}/toggle`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            });
            const data = await res.json();
            if (data.success) {
                btn.className = 'btn btn-toggle ' + (data.is_active ? 'active' : 'inactive');
                btn.innerHTML = `<i class="fas ${data.is_active ? 'fa-toggle-on' : 'fa-toggle-off'}"></i> ${data.is_active ? 'Activa' : 'Inactiva'}`;
                const statusBadge = btn.closest('.branch-item').querySelector('.branch-status');
                statusBadge.className = 'branch-status ' + (data.is_active ? 'active' : 'inactive');
                statusBadge.textContent = data.is_active ? 'Activa' : 'Inactiva';
            }
        }
    </script>
    <script src="/js/admin-notify.js"></script>
</body>
</html>
