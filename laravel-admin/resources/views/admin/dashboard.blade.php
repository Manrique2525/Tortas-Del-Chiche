<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin - Las Tortas Del Chiche</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f5f5f5; min-height: 100vh; }

        /* ── Header ── */
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
        .logout-btn {
            background: transparent; color: #ff6b6b; border: 2px solid #ff6b6b;
            padding: 7px 14px; border-radius: 8px; font-family: 'Poppins', sans-serif;
            font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;
            white-space: nowrap;
        }
        .logout-btn:hover { background: #ff6b6b; color: white; }

        /* ── Add Button ── */
        .add-btn {
            background: linear-gradient(135deg, #4CAF50, #45a049); color: white;
            border: none; padding: 7px 14px; border-radius: 8px; font-family: 'Poppins', sans-serif;
            font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;
            text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
            white-space: nowrap; position: relative;
        }
        .add-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(76,175,80,0.3); }
        .btn-label { display: inline; }

        /* ── Stats ── */
        .stats-row {
            display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;
            padding: 20px 20px 0;
        }
        .stat-card {
            background: white; border-radius: 12px; padding: 16px; text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .stat-card .stat-number { font-size: 1.8rem; font-weight: 700; color: #1a1a1a; }
        .stat-card .stat-label { font-size: 0.75rem; color: #888; margin-top: 2px; }
        .stat-card.active .stat-number { color: #4CAF50; }
        .stat-card.inactive .stat-number { color: #e74c3c; }

        /* ── Success Banner ── */
        .success-banner {
            margin: 16px 20px 0; padding: 12px 16px; background: #e8f5e9; border-radius: 10px;
            border-left: 4px solid #4CAF50; display: flex; align-items: center; gap: 10px;
            animation: slideDown 0.3s ease;
        }
        .success-banner i { color: #4CAF50; font-size: 1.1rem; }
        .success-banner span { color: #2e7d32; font-size: 0.85rem; font-weight: 600; }
        .success-banner .close-banner {
            margin-left: auto; background: none; border: none; color: #2e7d32;
            cursor: pointer; font-size: 1rem;
        }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        /* ── Content ── */
        .admin-content { padding: 20px 20px 40px; }
        .category-section { margin-bottom: 24px; }
        .category-header {
            display: flex; align-items: center; gap: 10px; padding: 12px 16px;
            background: white; border-radius: 12px 12px 0 0; border-bottom: 3px solid #FF6B35;
        }
        .category-header h2 { font-size: 0.95rem; font-weight: 700; color: #1a1a1a; }
        .category-header .category-count {
            background: #FF6B35; color: white; padding: 2px 10px; border-radius: 20px;
            font-size: 0.7rem; font-weight: 600;
        }
        .category-header i { font-size: 1.1rem; color: #FF6B35; }

        /* ── Product Rows ── */
        .product-list {
            background: white; border-radius: 0 0 12px 12px; overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .product-row {
            display: flex; align-items: center; padding: 12px 14px;
            border-bottom: 1px solid #f0f0f0; transition: background 0.2s ease;
        }
        .product-row:last-child { border-bottom: none; }
        .product-row:hover { background: #fafafa; }
        .product-row.sortable-ghost { opacity: 0.4; background: #fff5f0; }
        .product-row.sortable-drag { box-shadow: 0 5px 20px rgba(0,0,0,0.15); border-radius: 8px; }
        .product-row.sortable-chosen { background: #fff5f0; }

        .drag-handle {
            cursor: grab; color: #ccc; font-size: 1rem; padding: 4px 8px 4px 0;
            transition: color 0.2s ease; user-select: none; flex-shrink: 0;
        }
        .drag-handle:hover { color: #FF6B35; }
        .drag-handle:active { cursor: grabbing; }
        .product-row.inactive { opacity: 0.5; }
        .product-row.inactive .product-name { text-decoration: line-through; color: #999; }
        .product-info { flex: 1; display: flex; align-items: center; gap: 10px; min-width: 0; }
        .product-img {
            width: 44px; height: 44px; border-radius: 10px; object-fit: cover;
            background: #f0f0f0; flex-shrink: 0;
        }
        .product-details { flex: 1; min-width: 0; }
        .product-name { font-weight: 600; font-size: 0.85rem; color: #1a1a1a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .product-price { color: #FF6B35; font-weight: 700; font-size: 0.85rem; }
        .product-status { font-size: 0.7rem; color: #888; margin-top: 1px; }

        /* ── Action Buttons ── */
        .product-actions {
            display: flex; align-items: center; gap: 6px; margin-left: 10px; flex-shrink: 0;
        }
        .action-btn {
            width: 34px; height: 34px; border: none; border-radius: 8px;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: all 0.2s ease; font-size: 0.8rem;
        }
        .edit-btn { background: #e3f2fd; color: #1976d2; }
        .edit-btn:hover { background: #1976d2; color: white; }
        .delete-btn { background: #ffebee; color: #e53935; }
        .delete-btn:hover { background: #e53935; color: white; }

        /* ── Toggle Switch ── */
        .toggle { position: relative; width: 48px; height: 26px; flex-shrink: 0; margin-left: 6px; }
        .toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
        .toggle-slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background: #ddd; border-radius: 26px; transition: all 0.3s ease;
        }
        .toggle-slider::before {
            content: ""; position: absolute; height: 20px; width: 20px; left: 3px; bottom: 3px;
            background: white; border-radius: 50%; transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .toggle input:checked + .toggle-slider { background: #4CAF50; }
        .toggle input:checked + .toggle-slider::before { transform: translateX(22px); }
        .toggle input:focus + .toggle-slider { box-shadow: 0 0 0 3px rgba(76,175,80,0.3); }

        /* ── Toast ── */
        .toast {
            position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(100px);
            background: #1a1a1a; color: white; padding: 12px 24px; border-radius: 10px;
            font-size: 0.85rem; font-weight: 600; z-index: 1000; transition: transform 0.3s ease;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 10px;
            max-width: 90%;
        }
        .toast.show { transform: translateX(-50%) translateY(0); }
        .toast.success { border-left: 4px solid #4CAF50; }
        .toast.error { border-left: 4px solid #e74c3c; }

        /* ── Delete Modal ── */
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
        .modal-card .product-to-delete { font-weight: 700; color: #1a1a1a; }
        .modal-actions { display: flex; gap: 12px; }
        .modal-actions .btn {
            flex: 1; padding: 12px; border: none; border-radius: 10px;
            font-family: 'Poppins', sans-serif; font-size: 0.85rem; font-weight: 700;
            cursor: pointer; transition: all 0.2s ease;
        }
        .btn-cancel { background: #e0e0e0; color: #555; }
        .btn-cancel:hover { background: #d0d0d0; }
        .btn-danger { background: #e53935; color: white; }
        .btn-danger:hover { background: #c62828; }

        /* ── Footer ── */
        .admin-footer { text-align: center; padding: 20px; color: #aaa; font-size: 0.75rem; }

        /* ════════════════════════════════════════
           RESPONSIVE — 3 breakpoints
           ════════════════════════════════════════ */

        /* Tablet (< 768px) */
        @media (max-width: 768px) {
            .admin-header { padding: 14px 16px; }
            .header-actions { gap: 6px; }
            .add-btn { padding: 7px 12px; font-size: 0.7rem; }
            .add-btn .btn-label { display: none; }
            .logout-btn { padding: 7px 10px; font-size: 0.7rem; }
            .logout-btn .btn-label { display: none; }

            .stats-row { gap: 10px; padding: 16px 16px 0; }
            .admin-content { padding: 16px 16px 32px; }
            .success-banner { margin: 12px 16px 0; }
        }

        /* Mobile grande (< 576px) */
        @media (max-width: 576px) {
            .admin-header {
                padding: 12px 14px; flex-wrap: wrap; gap: 10px;
            }
            .admin-header-left { flex: 1; min-width: 0; }
            .admin-header-left img { width: 38px; height: 38px; }
            .admin-header-left h1 { font-size: 0.9rem; }
            .header-actions { width: 100%; justify-content: flex-end; }

            .stats-row {
                grid-template-columns: 1fr 1fr 1fr; gap: 8px; padding: 14px 14px 0;
            }
            .stat-card { padding: 12px 8px; }
            .stat-card .stat-number { font-size: 1.3rem; }
            .stat-card .stat-label { font-size: 0.65rem; }

            .category-header { padding: 10px 14px; }
            .category-header h2 { font-size: 0.85rem; }

            .product-row {
                padding: 10px 12px; gap: 6px;
            }
            .drag-handle { padding: 4px 4px 4px 0; font-size: 0.85rem; }
            .product-img { width: 38px; height: 38px; border-radius: 8px; }
            .product-name { font-size: 0.8rem; }
            .product-price { font-size: 0.8rem; }
            .product-status { font-size: 0.65rem; }

            .action-btn { width: 30px; height: 30px; font-size: 0.7rem; }
            .toggle { width: 44px; height: 24px; }
            .toggle-slider::before { height: 18px; width: 18px; }
            .toggle input:checked + .toggle-slider::before { transform: translateX(20px); }
        }

        /* Mobile pequeño (< 420px) */
        @media (max-width: 420px) {
            .admin-header { padding: 10px 12px; }
            .admin-header-left img { width: 34px; height: 34px; border-width: 2px; }
            .admin-header-left h1 { font-size: 0.82rem; }
            .admin-header-left p { font-size: 0.6rem; }
            .header-actions { gap: 5px; }
            .add-btn { padding: 6px 10px; font-size: 0.65rem; border-radius: 6px; }
            .logout-btn { padding: 6px 8px; font-size: 0.65rem; border-radius: 6px; }

            .stats-row { grid-template-columns: 1fr 1fr 1fr; gap: 6px; padding: 12px 12px 0; }
            .stat-card { padding: 10px 6px; border-radius: 10px; }
            .stat-card .stat-number { font-size: 1.15rem; }
            .stat-card .stat-label { font-size: 0.6rem; }

            .admin-content { padding: 12px 12px 28px; }
            .category-header { padding: 8px 12px; border-bottom-width: 2px; }
            .category-header h2 { font-size: 0.8rem; }
            .category-header .category-count { font-size: 0.6rem; padding: 1px 8px; }
            .category-header i { font-size: 0.9rem; }

            .product-row { padding: 8px 10px; }
            .drag-handle { padding: 2px 3px 2px 0; font-size: 0.75rem; }
            .product-img { width: 34px; height: 34px; border-radius: 7px; }
            .product-name { font-size: 0.75rem; }
            .product-price { font-size: 0.75rem; }
            .product-status { font-size: 0.6rem; }
            .product-actions { gap: 4px; margin-left: 6px; }
            .action-btn { width: 28px; height: 28px; border-radius: 6px; font-size: 0.65rem; }
            .toggle { width: 40px; height: 22px; margin-left: 4px; }
            .toggle-slider::before { height: 16px; width: 16px; left: 3px; bottom: 3px; }
            .toggle input:checked + .toggle-slider::before { transform: translateX(18px); }

            .admin-footer { padding: 16px; font-size: 0.7rem; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="admin-header">
        <div class="admin-header-left">
            <img src="/img/logo.jpeg" alt="Logo"
                 onerror="this.style.background='#FF6B35'; this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2245%22 height=%2245%22><rect width=%2245%22 height=%2245%22 fill=%22%23FF6B35%22 rx=%2222%22/><text x=%2222.5%22 y=%2229%22 text-anchor=%22middle%22 fill=%22white%22 font-size=%2216%22 font-family=%22sans-serif%22 font-weight=%22bold%22>TT</text></svg>'">
            <div>
                <h1>Las Tortas Del Chiche</h1>
                <p>Panel de Administración</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.orders') }}" class="add-btn" style="background: linear-gradient(135deg, #2196F3, #1976D2); position: relative;">
                <i class="fas fa-receipt"></i> <span class="btn-label">Pedidos</span>
                <span data-pending-badge style="position:absolute; top:-6px; right:-6px; background:#e74c3c; color:white; font-size:0.6rem; font-weight:700; min-width:16px; height:16px; border-radius:8px; display:flex; align-items:center; justify-content:center; border:2px solid #1a1a1a; {{ $pendingOrders > 0 ? '' : 'display:none;' }}">{{ $pendingOrders }}</span>
            </a>
            <a href="{{ route('admin.coupons') }}" class="add-btn" style="background: linear-gradient(135deg, #9C27B0, #7B1FA2);">
                <i class="fas fa-tags"></i> <span class="btn-label">Cupones</span>
            </a>
            <a href="{{ route('admin.products.create') }}" class="add-btn">
                <i class="fas fa-plus"></i> <span class="btn-label">Nuevo</span>
            </a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> <span class="btn-label">Salir</span>
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="success-banner" id="successBanner">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
            <button class="close-banner" onclick="document.getElementById('successBanner').style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-number">{{ $products->count() }}</div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card active">
            <div class="stat-number">{{ $totalActive }}</div>
            <div class="stat-label">Activos</div>
        </div>
        <div class="stat-card inactive">
            <div class="stat-number">{{ $totalInactive }}</div>
            <div class="stat-label">Inactivos</div>
        </div>
    </div>

    <!-- Products -->
    <div class="admin-content">
        @foreach(['comida' => 'fas fa-utensils', 'bebida' => 'fas fa-wine-glass-alt'] as $cat => $icon)
            @if($products->where('category', $cat)->count())
            <div class="category-section">
                <div class="category-header">
                    <i class="{{ $icon }}"></i>
                    <h2>{{ ucfirst($cat) }}</h2>
                    <span class="category-count">{{ $products->where('category', $cat)->count() }}</span>
                </div>
                <div class="product-list" data-category="{{ $cat }}">
                    @foreach($products->where('category', $cat) as $product)
                        <div class="product-row {{ $product->active ? '' : 'inactive' }}" id="product-row-{{ $product->id }}" data-id="{{ $product->id }}">
                            <span class="drag-handle" title="Arrastrar para reordenar"><i class="fas fa-grip-vertical"></i></span>
                            <div class="product-info">
                                <img class="product-img" src="/{{ ltrim($product->image, '/') }}" alt="{{ $product->name }}"
                                     onerror="this.style.background='#FF6B35'; this.style.display='flex'; this.style.alignItems='center'; this.style.justifyContent='center'; this.style.color='white'; this.style.fontSize='16px'; this.style.fontWeight='bold'; this.alt='{{ substr($product->name, 0, 2) }}'">
                                <div class="product-details">
                                    <div class="product-name">{{ $product->name }}</div>
                                    <div class="product-price">${{ number_format($product->price, 0) }}</div>
                                    <div class="product-status" id="status-{{ $product->id }}">
                                        {{ $product->active ? 'Activo' : 'Inactivo' }}
                                    </div>
                                </div>
                            </div>
                            <div class="product-actions">
                                <a href="{{ route('admin.products.edit', $product) }}" class="action-btn edit-btn" title="Editar">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <button class="action-btn delete-btn" title="Eliminar"
                                        onclick="confirmDelete({{ $product->id }}, '{{ addslashes($product->name) }}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <label class="toggle">
                                <input type="checkbox"
                                       data-product-id="{{ $product->id }}"
                                       {{ $product->active ? 'checked' : '' }}
                                       onchange="toggleProduct(this)">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        @endforeach
    </div>

    <div class="admin-footer">
        <p>Las Tortas Del Chiche &copy; {{ date('Y') }} — Panel Admin</p>
    </div>

    <!-- Delete Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-card">
            <h3><i class="fas fa-exclamation-triangle"></i> Eliminar producto</h3>
            <p>¿Estás seguro de eliminar <span class="product-to-delete" id="deleteProductName"></span>?</p>
            <form id="deleteForm" method="POST" style="display:none;">@csrf @method('DELETE')</form>
            <div class="modal-actions">
                <button class="btn btn-cancel" onclick="closeDeleteModal()">Cancelar</button>
                <button class="btn btn-danger" onclick="submitDelete()">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
    <script>
        function toggleProduct(checkbox) {
            const id = checkbox.dataset.productId;
            const row = document.getElementById(`product-row-${id}`);
            const status = document.getElementById(`status-${id}`);

            if (row) row.classList.toggle('inactive', !checkbox.checked);
            if (status) status.textContent = checkbox.checked ? 'Activo' : 'Inactivo';
            updateStats();

            fetch(`/admin/products/${id}/toggle`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json())
            .then(data => showToast(data.message, 'success'))
            .catch(() => {
                checkbox.checked = !checkbox.checked;
                if (row) row.classList.toggle('inactive', !checkbox.checked);
                if (status) status.textContent = checkbox.checked ? 'Activo' : 'Inactivo';
                updateStats();
                showToast('Error al actualizar', 'error');
            });
        }

        function updateStats() {
            const total = document.querySelectorAll('.toggle input[type="checkbox"]').length;
            const active = document.querySelectorAll('.toggle input:checked').length;
            const statCards = document.querySelectorAll('.stat-card .stat-number');
            if (statCards.length >= 3) {
                statCards[0].textContent = total;
                statCards[1].textContent = active;
                statCards[2].textContent = total - active;
            }
        }

        function showToast(message, type) {
            const toast = document.getElementById('toast');
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            toast.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
            toast.className = `toast ${type} show`;
            setTimeout(() => { toast.className = 'toast'; }, 2500);
        }

        let deleteId = null;
        function confirmDelete(id, name) {
            deleteId = id;
            document.getElementById('deleteProductName').textContent = name;
            document.getElementById('deleteForm').action = `/admin/products/${id}`;
            document.getElementById('deleteModal').classList.add('active');
        }
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            deleteId = null;
        }
        function submitDelete() {
            document.getElementById('deleteForm').submit();
        }

        document.querySelectorAll('.product-list').forEach(function(list) {
            Sortable.create(list, {
                handle: '.drag-handle',
                animation: 200,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                chosenClass: 'sortable-chosen',
                onEnd: function() {
                    var ids = [];
                    list.querySelectorAll('.product-row').forEach(function(row) {
                        ids.push(Number(row.dataset.id));
                    });
                    fetch('/admin/products/reorder', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ ids: ids }),
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(data) { showToast(data.message, 'success'); })
                    .catch(function() { showToast('Error al guardar orden', 'error'); });
                },
            });
        });
    </script>
    <script src="/js/admin-notify.js"></script>
</body>
</html>
