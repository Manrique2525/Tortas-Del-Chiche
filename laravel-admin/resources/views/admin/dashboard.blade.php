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

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }

        /* ── Header ── */
        .admin-header {
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .admin-header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .admin-header-left img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 2px solid #FF6B35;
        }

        .admin-header-left h1 {
            color: #FFD700;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .admin-header-left p {
            color: #aaa;
            font-size: 0.75rem;
        }

        .logout-btn {
            background: transparent;
            color: #ff6b6b;
            border: 2px solid #ff6b6b;
            padding: 8px 16px;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #ff6b6b;
            color: white;
        }

        /* ── Stats ── */
        .stats-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
            padding: 20px 24px 0;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .stat-card .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1a1a1a;
        }

        .stat-card .stat-label {
            font-size: 0.75rem;
            color: #888;
            margin-top: 2px;
        }

        .stat-card.active .stat-number { color: #4CAF50; }
        .stat-card.inactive .stat-number { color: #e74c3c; }

        /* ── Content ── */
        .admin-content {
            padding: 20px 24px 40px;
        }

        .category-section {
            margin-bottom: 24px;
        }

        .category-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: white;
            border-radius: 12px 12px 0 0;
            border-bottom: 3px solid #FF6B35;
        }

        .category-header h2 {
            font-size: 1rem;
            font-weight: 700;
            color: #1a1a1a;
        }

        .category-header .category-count {
            background: #FF6B35;
            color: white;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .category-header i {
            font-size: 1.2rem;
            color: #FF6B35;
        }

        /* ── Product Rows ── */
        .product-list {
            background: white;
            border-radius: 0 0 12px 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .product-row {
            display: flex;
            align-items: center;
            padding: 14px 16px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s ease;
        }

        .product-row:last-child {
            border-bottom: none;
        }

        .product-row:hover {
            background: #fafafa;
        }

        .product-row.inactive {
            opacity: 0.5;
        }

        .product-row.inactive .product-name {
            text-decoration: line-through;
            color: #999;
        }

        .product-info {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product-img {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            object-fit: cover;
            background: #f0f0f0;
            flex-shrink: 0;
        }

        .product-details {
            flex: 1;
        }

        .product-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: #1a1a1a;
        }

        .product-price {
            color: #FF6B35;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .product-status {
            font-size: 0.7rem;
            color: #888;
            margin-top: 2px;
        }

        /* ── Toggle Switch ── */
        .toggle {
            position: relative;
            width: 52px;
            height: 28px;
            flex-shrink: 0;
            margin-left: 12px;
        }

        .toggle input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background: #ddd;
            border-radius: 28px;
            transition: all 0.3s ease;
        }

        .toggle-slider::before {
            content: "";
            position: absolute;
            height: 22px;
            width: 22px;
            left: 3px;
            bottom: 3px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .toggle input:checked + .toggle-slider {
            background: #4CAF50;
        }

        .toggle input:checked + .toggle-slider::before {
            transform: translateX(24px);
        }

        .toggle input:focus + .toggle-slider {
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.3);
        }

        /* ── Toast ── */
        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #1a1a1a;
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            z-index: 1000;
            transition: transform 0.3s ease;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .toast.show {
            transform: translateX(-50%) translateY(0);
        }

        .toast.success { border-left: 4px solid #4CAF50; }
        .toast.error { border-left: 4px solid #e74c3c; }

        /* ── Footer ── */
        .admin-footer {
            text-align: center;
            padding: 20px;
            color: #aaa;
            font-size: 0.8rem;
        }

        /* ── Mobile ── */
        @media (max-width: 480px) {
            .stats-row {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .stat-card {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 12px 16px;
                text-align: left;
            }

            .stat-card .stat-number {
                font-size: 1.4rem;
            }

            .product-row {
                padding: 12px;
            }

            .product-img {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="admin-header">
        <div class="admin-header-left">
            <img src="https://lastortasdelchiche.com/img/imagenes/logo1.jpg" alt="Logo"
                 onerror="this.style.background='#FF6B35'; this.style.display='flex'; this.style.alignItems='center'; this.style.justifyContent='center'; this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2245%22 height=%2245%22><rect width=%2245%22 height=%2245%22 fill=%22%23FF6B35%22 rx=%2222%22/><text x=%2222.5%22 y=%2229%22 text-anchor=%22middle%22 fill=%22white%22 font-size=%2216%22 font-family=%22sans-serif%22 font-weight=%22bold%22>TT</text></svg>'">
            <div>
                <h1>Las Tortas Del Chiche</h1>
                <p>Panel de Administración</p>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Salir
            </button>
        </form>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-number">{{ $products->count() }}</div>
            <div class="stat-label">Total productos</div>
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
        <!-- Comida -->
        <div class="category-section">
            <div class="category-header">
                <i class="fas fa-utensils"></i>
                <h2>Comida</h2>
                <span class="category-count">{{ $products->where('category', 'comida')->count() }}</span>
            </div>
            <div class="product-list">
                @foreach($products->where('category', 'comida') as $product)
                    <div class="product-row {{ $product->active ? '' : 'inactive' }}" id="product-row-{{ $product->id }}">
                        <div class="product-info">
                            <img class="product-img" src="{{ $product->image }}" alt="{{ $product->name }}"
                                 onerror="this.style.background='#FF6B35'; this.style.display='flex'; this.style.alignItems='center'; this.style.justifyContent='center'; this.style.color='white'; this.style.fontSize='16px'; this.style.fontWeight='bold'; this.alt='{{ substr($product->name, 0, 2) }}'">
                            <div class="product-details">
                                <div class="product-name">{{ $product->name }}</div>
                                <div class="product-price">${{ number_format($product->price, 0) }}</div>
                                <div class="product-status" id="status-{{ $product->id }}">
                                    {{ $product->active ? 'Activo' : 'Inactivo' }}
                                </div>
                            </div>
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

        <!-- Bebidas -->
        <div class="category-section">
            <div class="category-header">
                <i class="fas fa-wine-glass-alt"></i>
                <h2>Bebidas</h2>
                <span class="category-count">{{ $products->where('category', 'bebida')->count() }}</span>
            </div>
            <div class="product-list">
                @foreach($products->where('category', 'bebida') as $product)
                    <div class="product-row {{ $product->active ? '' : 'inactive' }}" id="product-row-{{ $product->id }}">
                        <div class="product-info">
                            <img class="product-img" src="{{ $product->image }}" alt="{{ $product->name }}"
                                 onerror="this.style.background='#FF6B35'; this.style.display='flex'; this.style.alignItems='center'; this.style.justifyContent='center'; this.style.color='white'; this.style.fontSize='16px'; this.style.fontWeight='bold'; this.alt='{{ substr($product->name, 0, 2) }}'">
                            <div class="product-details">
                                <div class="product-name">{{ $product->name }}</div>
                                <div class="product-price">${{ number_format($product->price, 0) }}</div>
                                <div class="product-status" id="status-{{ $product->id }}">
                                    {{ $product->active ? 'Activo' : 'Inactivo' }}
                                </div>
                            </div>
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
    </div>

    <div class="admin-footer">
        <p>Las Tortas Del Chiche &copy; {{ date('Y') }} — Panel Admin</p>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <script>
        function toggleProduct(checkbox) {
            const id = checkbox.dataset.productId;
            const row = document.getElementById(`product-row-${id}`);
            const status = document.getElementById(`status-${id}`);

            // Optimistic UI
            if (row) row.classList.toggle('inactive', !checkbox.checked);
            if (status) status.textContent = checkbox.checked ? 'Activo' : 'Inactivo';

            // Recalcular stats
            updateStats();

            fetch(`/admin/products/${id}/toggle`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
            .then(r => r.json())
            .then(data => {
                showToast(data.message, 'success');
            })
            .catch(err => {
                // Revert on error
                checkbox.checked = !checkbox.checked;
                if (row) row.classList.toggle('inactive', !checkbox.checked);
                if (status) status.textContent = checkbox.checked ? 'Activo' : 'Inactivo';
                updateStats();
                showToast('Error al actualizar', 'error');
            });
        }

        function updateStats() {
            const allCheckboxes = document.querySelectorAll('.toggle input[type="checkbox"]');
            const total = allCheckboxes.length;
            const active = document.querySelectorAll('.toggle input:checked').length;
            const inactive = total - active;

            const statCards = document.querySelectorAll('.stat-card .stat-number');
            if (statCards.length >= 3) {
                statCards[0].textContent = total;
                statCards[1].textContent = active;
                statCards[2].textContent = inactive;
            }
        }

        function showToast(message, type) {
            const toast = document.getElementById('toast');
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            toast.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
            toast.className = `toast ${type} show`;
            setTimeout(() => { toast.className = 'toast'; }, 2500);
        }
    </script>
</body>
</html>
