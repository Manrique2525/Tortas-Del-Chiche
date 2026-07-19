<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Disponibilidad por Sucursal - Admin</title>
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
        .admin-header-left h1 { color: #FFD700; font-size: 1rem; font-weight: 700; }
        .admin-header-left p { color: #aaa; font-size: 0.7rem; }
        .header-actions { display: flex; gap: 8px; align-items: center; flex-shrink: 0; }
        .back-btn {
            background: transparent; color: #FF6B35; border: 2px solid #FF6B35;
            padding: 7px 14px; border-radius: 8px; font-family: 'Poppins', sans-serif;
            font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;
            text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
        }
        .back-btn:hover { background: #FF6B35; color: white; }

        .admin-content { padding: 20px; max-width: 1200px; margin: 0 auto; }

        .page-title { font-size: 1.3rem; font-weight: 700; color: #1a1a1a; margin-bottom: 6px; }
        .page-desc { color: #888; font-size: 0.85rem; margin-bottom: 24px; }

        .branch-tabs {
            display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;
        }
        .branch-tab {
            padding: 10px 20px; border-radius: 10px; border: 2px solid #e0e0e0;
            background: white; cursor: pointer; font-family: 'Poppins', sans-serif;
            font-size: 0.85rem; font-weight: 600; transition: all 0.3s ease; color: #555;
        }
        .branch-tab:hover { border-color: #FF6B35; color: #FF6B35; }
        .branch-tab.active {
            background: #FF6B35; color: white; border-color: #FF6B35;
            box-shadow: 0 4px 12px rgba(255,107,53,0.3);
        }
        .branch-tab .branch-status {
            display: inline-block; width: 8px; height: 8px; border-radius: 50%;
            margin-right: 6px;
        }
        .branch-tab .branch-status.open { background: #4CAF50; }
        .branch-tab .branch-status.closed { background: #e74c3c; }

        .product-section { margin-bottom: 24px; }
        .product-section-title {
            font-size: 0.9rem; font-weight: 700; color: #1a1a1a;
            padding: 10px 16px; background: white; border-radius: 10px 10px 0 0;
            border-bottom: 3px solid #FF6B35; display: flex; align-items: center; gap: 8px;
        }
        .product-section-title i { color: #FF6B35; }

        .product-grid {
            background: white; border-radius: 0 0 10px 10px; overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .product-item {
            display: flex; align-items: center; padding: 12px 16px;
            border-bottom: 1px solid #f0f0f0; gap: 12px;
        }
        .product-item:last-child { border-bottom: none; }

        .product-img {
            width: 40px; height: 40px; border-radius: 8px; object-fit: cover;
            background: #f0f0f0; flex-shrink: 0;
        }
        .product-info { flex: 1; min-width: 0; }
        .product-name { font-weight: 600; font-size: 0.85rem; color: #1a1a1a; }
        .product-price { font-size: 0.75rem; color: #FF6B35; font-weight: 600; }
        .product-global-options {
            font-size: 0.65rem; color: #999; margin-top: 2px;
        }

        .branch-controls {
            display: flex; align-items: center; gap: 10px; flex-shrink: 0; flex-wrap: wrap;
        }
        .option-check-group {
            display: flex; gap: 4px; align-items: center;
        }
        .option-check {
            display: none;
        }
        .option-check-label {
            padding: 4px 10px; border-radius: 6px; font-size: 0.65rem; font-weight: 600;
            cursor: pointer; transition: all 0.2s ease; border: 2px solid transparent;
            user-select: none;
        }
        .option-check-label.mojado {
            background: #fff5f0; color: #FF6B35; border-color: #FF6B35;
        }
        .option-check-label.mojado.active { background: #FF6B35; color: white; }
        .option-check-label.seco {
            background: #e3f2fd; color: #1976d2; border-color: #1976d2;
        }
        .option-check-label.seco.active { background: #1976d2; color: white; }
        .option-check-label.cochinita {
            background: #f3e5f5; color: #9C27B0; border-color: #9C27B0;
        }
        .option-check-label.cochinita.active { background: #9C27B0; color: white; }
        .option-check-label.lechon {
            background: #e8f5e9; color: #4CAF50; border-color: #4CAF50;
        }
        .option-check-label.lechon.active { background: #4CAF50; color: white; }

        .price-override-input {
            width: 80px; padding: 6px 8px; border: 2px solid #e0e0e0;
            border-radius: 6px; font-family: 'Poppins', sans-serif;
            font-size: 0.75rem; text-align: center; transition: border-color 0.2s;
        }
        .price-override-input:focus { outline: none; border-color: #FF6B35; }
        .price-override-input::placeholder { color: #bbb; font-size: 0.65rem; }

        .branch-toggle {
            position: relative; width: 44px; height: 24px; flex-shrink: 0;
        }
        .branch-toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
        .branch-toggle-slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background: #ddd; border-radius: 24px; transition: all 0.3s ease;
        }
        .branch-toggle-slider::before {
            content: ""; position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px;
            background: white; border-radius: 50%; transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .branch-toggle input:checked + .branch-toggle-slider { background: #4CAF50; }
        .branch-toggle input:checked + .branch-toggle-slider::before { transform: translateX(20px); }

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

        .branch-content { display: none; }
        .branch-content.active { display: block; }

        .product-item.branch-inactive { opacity: 0.45; }

        .admin-footer { text-align: center; padding: 20px; color: #aaa; font-size: 0.75rem; }

        @media (max-width: 768px) {
            .admin-header { padding: 14px 16px; }
            .admin-content { padding: 16px; }
            .product-item { flex-wrap: wrap; padding: 10px 14px; }
            .branch-controls { width: 100%; justify-content: flex-start; margin-top: 6px; }
            .price-override-input { width: 70px; }
        }
        @media (max-width: 576px) {
            .branch-tabs { gap: 6px; }
            .branch-tab { padding: 8px 14px; font-size: 0.75rem; }
            .option-check-label { padding: 3px 8px; font-size: 0.6rem; }
            .product-item { padding: 8px 12px; }
            .product-name { font-size: 0.8rem; }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="admin-header-left">
            <img src="/img/logo.jpeg" alt="Logo">
            <div>
                <h1>Las Tortas Del Chiche</h1>
                <p>Disponibilidad por Sucursal</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.dashboard') }}" class="back-btn">
                <i class="fas fa-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </div>

    <div class="admin-content">
        <h1 class="page-title"><i class="fas fa-store-alt"></i> Disponibilidad por Sucursal</h1>
        <p class="page-desc">Configura qué productos están disponibles en cada sucursal, sus opciones y precios.</p>

        <div class="branch-tabs" id="branchTabs">
            @foreach($branches as $branch)
                <button class="branch-tab {{ $loop->first ? 'active' : '' }}" data-branch-id="{{ $branch->id }}">
                    <span class="branch-status {{ $branch->is_open ? 'open' : 'closed' }}"></span>
                    {{ $branch->name }}
                </button>
            @endforeach
        </div>

        @foreach($branches as $branch)
            <div class="branch-content {{ $loop->first ? 'active' : '' }}" id="branchContent{{ $branch->id }}">
                @foreach(['comida' => 'fas fa-utensils', 'bebida' => 'fas fa-wine-glass-alt'] as $cat => $icon)
                    @php $catProducts = $products->where('category', $cat); @endphp
                    @if($catProducts->count())
                        <div class="product-section">
                            <div class="product-section-title">
                                <i class="{{ $icon }}"></i>
                                <span>{{ ucfirst($cat) }}</span>
                                <span style="font-weight:400;color:#999;font-size:0.75rem;">({{ $catProducts->count() }})</span>
                            </div>
                            <div class="product-grid">
                                @foreach($catProducts as $product)
                                    @php
                                        $bp = $branchProductMap[$branch->id][$product->id] ?? null;
                                        $bpActive = $bp ? (bool) $bp->active : $product->active;
                                        $bpOptions = $bp ? json_decode($bp->available_options ?? 'null', true) : null;
                                        $bpPrice = $bp ? $bp->price_override : null;

                                        $hasMojado = $bpOptions ? in_array('mojado', $bpOptions['type'] ?? []) : $product->has_mojado;
                                        $hasSeco = $bpOptions ? in_array('seco', $bpOptions['type'] ?? []) : $product->has_seco;
                                        $hasCochinita = $bpOptions ? in_array('cochinita', $bpOptions['meat'] ?? []) : $product->has_cochinita;
                                        $hasLechon = $bpOptions ? in_array('lechon', $bpOptions['meat'] ?? []) : $product->has_lechon;
                                    @endphp
                                    <div class="product-item {{ $bpActive ? '' : 'branch-inactive' }}" id="bp-row-{{ $branch->id }}-{{ $product->id }}">
                                        <img class="product-img" src="/{{ ltrim($product->image, '/') }}" alt="{{ $product->name }}">
                                        <div class="product-info">
                                            <div class="product-name">{{ $product->name }}</div>
                                            <div class="product-price">
                                                ${{ number_format($product->price, 0) }}
                                                @if($bpPrice)
                                                    <span style="color:#4CAF50;font-size:0.65rem;">→ ${{ number_format($bpPrice, 0) }}</span>
                                                @endif
                                            </div>
                                            <div class="product-global-options">
                                                Global:
                                                {{ $product->has_mojado ? 'Mojado' : '' }}
                                                {{ $product->has_seco ? 'Seco' : '' }}
                                                {{ $product->has_cochinita ? 'Cochinita' : '' }}
                                                {{ $product->has_lechon ? 'Lechón' : '' }}
                                                @if(!$product->has_mojado && !$product->has_seco && !$product->has_cochinita && !$product->has_lechon)
                                                    Sin opciones
                                                @endif
                                            </div>
                                        </div>
                                        <div class="branch-controls">
                                            <div class="option-check-group">
                                                <input type="checkbox" class="option-check" id="mojado-{{ $branch->id }}-{{ $product->id }}"
                                                    data-branch="{{ $branch->id }}" data-product="{{ $product->id }}" data-option="mojado"
                                                    {{ $hasMojado ? 'checked' : '' }}>
                                                <label class="option-check-label mojado {{ $hasMojado ? 'active' : '' }}"
                                                    for="mojado-{{ $branch->id }}-{{ $product->id }}">Mojado</label>

                                                <input type="checkbox" class="option-check" id="seco-{{ $branch->id }}-{{ $product->id }}"
                                                    data-branch="{{ $branch->id }}" data-product="{{ $product->id }}" data-option="seco"
                                                    {{ $hasSeco ? 'checked' : '' }}>
                                                <label class="option-check-label seco {{ $hasSeco ? 'active' : '' }}"
                                                    for="seco-{{ $branch->id }}-{{ $product->id }}">Seco</label>

                                                <input type="checkbox" class="option-check" id="cochinita-{{ $branch->id }}-{{ $product->id }}"
                                                    data-branch="{{ $branch->id }}" data-product="{{ $product->id }}" data-option="cochinita"
                                                    {{ $hasCochinita ? 'checked' : '' }}>
                                                <label class="option-check-label cochinita {{ $hasCochinita ? 'active' : '' }}"
                                                    for="cochinita-{{ $branch->id }}-{{ $product->id }}">Cochinita</label>

                                                <input type="checkbox" class="option-check" id="lechon-{{ $branch->id }}-{{ $product->id }}"
                                                    data-branch="{{ $branch->id }}" data-product="{{ $product->id }}" data-option="lechon"
                                                    {{ $hasLechon ? 'checked' : '' }}>
                                                <label class="option-check-label lechon {{ $hasLechon ? 'active' : '' }}"
                                                    for="lechon-{{ $branch->id }}-{{ $product->id }}">Lechón</label>
                                            </div>

                                            <input type="number" class="price-override-input"
                                                placeholder="$ precio" step="0.50" min="0"
                                                value="{{ $bpPrice ?? '' }}"
                                                data-branch="{{ $branch->id }}" data-product="{{ $product->id }}">

                                            <label class="branch-toggle">
                                                <input type="checkbox" class="bp-toggle"
                                                    data-branch="{{ $branch->id }}" data-product="{{ $product->id }}"
                                                    {{ $bpActive ? 'checked' : '' }}>
                                                <span class="branch-toggle-slider"></span>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>

    <div class="admin-footer">
        <p>Las Tortas Del Chiche &copy; {{ date('Y') }} — Disponibilidad por Sucursal</p>
    </div>

    <div class="toast" id="toast"></div>

    <script>
        // ── Branch tabs ──
        document.querySelectorAll('.branch-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.branch-tab').forEach(function(t) { t.classList.remove('active'); });
                document.querySelectorAll('.branch-content').forEach(function(c) { c.classList.remove('active'); });
                tab.classList.add('active');
                document.getElementById('branchContent' + tab.dataset.branchId).classList.add('active');
            });
        });

        // ── Toggle active ──
        document.querySelectorAll('.bp-toggle').forEach(function(toggle) {
            toggle.addEventListener('change', function() {
                var branchId = this.dataset.branch;
                var productId = this.dataset.product;
                var active = this.checked;
                var row = document.getElementById('bp-row-' + branchId + '-' + productId);
                if (row) row.classList.toggle('branch-inactive', !active);
                saveProduct(branchId, productId, { active: active });
            });
        });

        // ── Option checkboxes ──
        document.querySelectorAll('.option-check').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                var label = this.nextElementSibling;
                if (this.checked) {
                    label.classList.add('active');
                } else {
                    label.classList.remove('active');
                }
                var branchId = this.dataset.branch;
                var productId = this.dataset.product;
                saveProduct(branchId, productId, {});
            });
        });

        // ── Price override ──
        var priceInputs = document.querySelectorAll('.price-override-input');
        var priceTimers = {};
        priceInputs.forEach(function(input) {
            input.addEventListener('input', function() {
                var branchId = this.dataset.branch;
                var productId = this.dataset.product;
                var key = branchId + '-' + productId;
                clearTimeout(priceTimers[key]);
                priceTimers[key] = setTimeout(function() {
                    saveProduct(branchId, productId, { price_override: input.value || null });
                }, 500);
            });
        });

        // ── Save function ──
        function saveProduct(branchId, productId, extra) {
            var row = document.getElementById('bp-row-' + branchId + '-' + productId);
            var data = {
                branch_id: branchId,
                product_id: productId,
                active: row ? !row.classList.contains('branch-inactive') : true,
                has_mojado: document.getElementById('mojado-' + branchId + '-' + productId)?.checked || false,
                has_seco: document.getElementById('seco-' + branchId + '-' + productId)?.checked || false,
                has_cochinita: document.getElementById('cochinita-' + branchId + '-' + productId)?.checked || false,
                has_lechon: document.getElementById('lechon-' + branchId + '-' + productId)?.checked || false,
                price_override: document.querySelector('.price-override-input[data-branch="' + branchId + '"][data-product="' + productId + '"]')?.value || null,
                ...extra,
            };
            if (data.price_override === '') data.price_override = null;

            fetch('/admin/branch-products/update', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data),
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                } else {
                    showToast('Error al guardar', 'error');
                }
            })
            .catch(function() {
                showToast('Error de conexión', 'error');
            });
        }

        function showToast(message, type) {
            var toast = document.getElementById('toast');
            var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            toast.innerHTML = '<i class="fas ' + icon + '"></i> ' + message;
            toast.className = 'toast ' + type + ' show';
            setTimeout(function() { toast.className = 'toast'; }, 2000);
        }
    </script>
</body>
</html>
