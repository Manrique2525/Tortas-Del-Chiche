<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pedidos - Admin</title>
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

        .stats-row { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr; gap: 12px; padding: 20px 24px 0; }
        .stat-card {
            background: white; border-radius: 12px; padding: 16px; text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .stat-card .stat-number { font-size: 1.6rem; font-weight: 700; color: #1a1a1a; }
        .stat-card .stat-label { font-size: 0.7rem; color: #888; margin-top: 2px; }
        .stat-card.pending .stat-number { color: #f39c12; }
        .stat-card.accepted .stat-number { color: #2196F3; }
        .stat-card.revenue .stat-number { color: #27ae60; }
        .stat-card.delivery .stat-number { color: #9C27B0; }

        .filters-bar {
            padding: 16px 24px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;
        }
        .filter-input {
            padding: 10px 14px; border: 2px solid #e0e0e0; border-radius: 10px;
            font-family: 'Poppins', sans-serif; font-size: 0.85rem; color: #333;
            background: white; transition: border-color 0.3s ease;
        }
        .filter-input:focus { outline: none; border-color: #FF6B35; }
        .filter-btn {
            padding: 10px 18px; border: none; border-radius: 10px;
            font-family: 'Poppins', sans-serif; font-size: 0.85rem; font-weight: 600;
            cursor: pointer; transition: all 0.2s ease;
        }
        .filter-btn-primary { background: #FF6B35; color: white; }
        .filter-btn-primary:hover { background: #FF8C42; }
        .filter-btn-secondary { background: #e0e0e0; color: #555; }
        .filter-btn-secondary:hover { background: #d0d0d0; }

        .orders-container { padding: 0 24px 40px; }

        .order-card {
            background: white; border-radius: 12px; margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow: hidden;
            border-left: 4px solid transparent;
        }
        .order-card.is-pendiente { border-left-color: #f39c12; }
        .order-card.is-aceptado { border-left-color: #2196F3; }
        .order-card.is-en_preparacion { border-left-color: #3498db; }
        .order-card.is-entregado { border-left-color: #27ae60; }
        .order-card.is-cancelado { border-left-color: #e74c3c; }

        .order-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 16px; border-bottom: 1px solid #f0f0f0; flex-wrap: wrap; gap: 8px;
        }
        .order-id { font-weight: 700; color: #1a1a1a; font-size: 0.9rem; }
        .order-date { color: #888; font-size: 0.75rem; margin-left: 8px; }
        .order-status {
            padding: 4px 12px; border-radius: 20px; font-size: 0.75rem;
            font-weight: 700; color: white; display: inline-flex; align-items: center; gap: 6px;
        }

        .order-body { padding: 14px 16px; }
        .order-customer { font-weight: 600; color: #1a1a1a; font-size: 0.9rem; margin-bottom: 6px; }
        .order-detail { color: #666; font-size: 0.8rem; margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }
        .order-detail i { color: #888; width: 16px; text-align: center; }
        .order-items { margin-top: 10px; padding-top: 10px; border-top: 1px solid #f0f0f0; }
        .order-item { font-size: 0.8rem; color: #555; padding: 2px 0; }
        .order-item span { font-weight: 600; color: #333; }

        .order-proof { margin-top: 12px; padding-top: 10px; border-top: 1px solid #f0f0f0; }
        .order-proof-label { font-size: 0.8rem; font-weight: 600; color: #27ae60; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
        .order-proof-link { display: inline-block; }
        .order-proof-img { max-width: 200px; max-height: 200px; border-radius: 10px; border: 2px solid #e0e0e0; object-fit: cover; cursor: pointer; transition: transform 0.2s ease; }
        .order-proof-img:hover { transform: scale(1.05); border-color: #27ae60; }

        .proof-modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85); z-index: 9999; justify-content: center; align-items: center;
            padding: 20px; cursor: pointer;
        }
        .proof-modal-overlay.active { display: flex; }
        .proof-modal-img { max-width: 90%; max-height: 90vh; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); object-fit: contain; }
        .proof-modal-close {
            position: absolute; top: 20px; right: 24px; background: rgba(255,255,255,0.15);
            color: white; border: none; width: 40px; height: 40px; border-radius: 50%;
            font-size: 1.2rem; cursor: pointer; transition: background 0.3s ease;
            display: flex; align-items: center; justify-content: center;
        }
        .proof-modal-close:hover { background: rgba(255,255,255,0.3); }

        .order-footer {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 16px; background: #fafafa; border-top: 1px solid #f0f0f0;
            flex-wrap: wrap; gap: 8px;
        }
        .order-total { font-weight: 700; color: #FF6B35; font-size: 1.1rem; }
        .order-payment {
            font-size: 0.75rem; color: #888; display: flex; align-items: center; gap: 4px;
        }

        .action-buttons { display: flex; gap: 8px; align-items: center; }
        .action-btn {
            padding: 8px 16px; border: none; border-radius: 8px;
            font-family: 'Poppins', sans-serif; font-size: 0.8rem; font-weight: 700;
            cursor: pointer; transition: all 0.2s ease; display: inline-flex;
            align-items: center; gap: 6px;
        }
        .btn-accept { background: #27ae60; color: white; }
        .btn-accept:hover { background: #219a52; transform: translateY(-1px); }
        .btn-reject { background: #e74c3c; color: white; }
        .btn-reject:hover { background: #c0392b; transform: translateY(-1px); }
        .btn-preparing { background: #3498db; color: white; }
        .btn-preparing:hover { background: #2980b9; }
        .btn-delivered { background: #27ae60; color: white; }
        .btn-delivered:hover { background: #219a52; }

        .status-select {
            padding: 6px 10px; border: 2px solid #e0e0e0; border-radius: 8px;
            font-family: 'Poppins', sans-serif; font-size: 0.8rem; font-weight: 600;
            cursor: pointer; background: white; transition: border-color 0.3s ease;
        }
        .status-select:focus { outline: none; border-color: #FF6B35; }

        .empty-state { text-align: center; padding: 60px 20px; color: #aaa; }
        .empty-state i { font-size: 3rem; margin-bottom: 16px; }
        .empty-state p { font-size: 0.95rem; }

        .toast {
            position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(100px);
            background: #1a1a1a; color: white; padding: 12px 24px; border-radius: 10px;
            font-size: 0.85rem; font-weight: 600; z-index: 1000; transition: transform 0.3s ease;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 10px;
        }
        .toast.show { transform: translateX(-50%) translateY(0); }
        .toast.success { border-left: 4px solid #4CAF50; }
        .toast.error { border-left: 4px solid #e74c3c; }

        .admin-footer { text-align: center; padding: 20px; color: #aaa; font-size: 0.8rem; }

        @media (max-width: 600px) {
            .stats-row { grid-template-columns: 1fr 1fr; }
            .filters-bar { flex-direction: column; }
            .filter-input { width: 100%; }
            .order-header, .order-footer { flex-direction: column; align-items: flex-start; }
            .action-buttons { width: 100%; }
            .action-btn { flex: 1; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="admin-header-left">
            <img src="https://lastortasdelchiche.com/img/imagenes/logo1.jpg" alt="Logo"
                 onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2245%22 height=%2245%22><rect width=%2245%22 height=%2245%22 fill=%22%23FF6B35%22 rx=%2222%22/><text x=%2222.5%22 y=%2229%22 text-anchor=%22middle%22 fill=%22white%22 font-size=%2216%22 font-family=%22sans-serif%22 font-weight=%22bold%22>TT</text></svg>'">
            <div>
                <h1>Las Tortas Del Chiche</h1>
                <p>Historial de Pedidos</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.dashboard') }}" class="back-btn"><i class="fas fa-arrow-left"></i> Productos</a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Salir</button>
            </form>
        </div>
    </div>

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-number">{{ $stats['total'] }}</div>
            <div class="stat-label">Total pedidos</div>
        </div>
        <div class="stat-card pending">
            <div class="stat-number">{{ $stats['pendiente'] }}</div>
            <div class="stat-label">Pendientes</div>
        </div>
        <div class="stat-card accepted">
            <div class="stat-number">{{ $stats['aceptado'] }}</div>
            <div class="stat-label">Aceptados</div>
        </div>
        <div class="stat-card revenue">
            <div class="stat-number">${{ number_format($stats['ingresos_hoy'], 0) }}</div>
            <div class="stat-label">Ingresos hoy</div>
        </div>
        <div class="stat-card delivery">
            <div class="stat-number">${{ number_format($stats['envios_hoy'], 0) }}</div>
            <div class="stat-label">Envíos repartidor</div>
        </div>
    </div>

    <form class="filters-bar" method="GET">
        <input type="text" name="search" class="filter-input" placeholder="Buscar nombre o teléfono..."
               value="{{ request('search') }}">
        <select name="status" class="filter-input">
            <option value="">Todos los estados</option>
            <option value="pendiente" {{ request('status') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
            <option value="aceptado" {{ request('status') === 'aceptado' ? 'selected' : '' }}>Aceptado</option>
            <option value="en_preparacion" {{ request('status') === 'en_preparacion' ? 'selected' : '' }}>En Preparación</option>
            <option value="entregado" {{ request('status') === 'entregado' ? 'selected' : '' }}>Entregado</option>
            <option value="cancelado" {{ request('status') === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
        </select>
        <select name="branch" class="filter-input">
            <option value="">Todas las sucursales</option>
            <option value="atasta" {{ request('branch') === 'atasta' ? 'selected' : '' }}>Atasta</option>
            <option value="av_universidad" {{ request('branch') === 'av_universidad' ? 'selected' : '' }}>AV Universidad</option>
        </select>
        <input type="date" name="date_from" class="filter-input" value="{{ request('date_from') }}">
        <input type="date" name="date_to" class="filter-input" value="{{ request('date_to') }}">
        <button type="submit" class="filter-btn filter-btn-primary"><i class="fas fa-search"></i> Filtrar</button>
        @if(request()->hasAny(['search','status','branch','date_from','date_to']))
            <a href="{{ route('admin.orders') }}" class="filter-btn filter-btn-secondary">Limpiar</a>
        @endif
    </form>

    <div class="orders-container">
        @forelse($orders as $order)
            <div class="order-card is-{{ $order->status }}" id="order-{{ $order->id }}">
                <div class="order-header">
                    <div>
                        <span class="order-id">#{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</span>
                        <span class="order-date">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="order-status" style="background: {{ $order->status_color }}">
                        <i class="fas fa-{{ match($order->status) {
                            'pendiente' => 'clock',
                            'aceptado' => 'check',
                            'en_preparacion' => 'fire',
                            'entregado' => 'check-double',
                            'cancelado' => 'times',
                            default => 'circle',
                        } }}"></i>
                        {{ $order->status_label }}
                    </div>
                </div>

                <div class="order-body">
                    <div class="order-customer">
                        <i class="fas fa-user"></i> {{ $order->customer_name }}
                    </div>
                    @if($order->customer_phone)
                        <div class="order-detail"><i class="fas fa-phone"></i> {{ $order->customer_phone }}</div>
                    @endif
                    <div class="order-detail">
                        <i class="fas fa-map-marker-alt"></i>
                        {{ $order->delivery_type === 'recoger' ? 'Recoger en sucursal' : ($order->customer_address ?: 'Sin dirección') }}
                    </div>
                    <div class="order-detail">
                        <i class="fas fa-store"></i> {{ ucfirst(str_replace('_', ' ', $order->branch)) }}
                    </div>
                    @if($order->coupon_code)
                        <div class="order-detail"><i class="fas fa-tag"></i> Cupón: {{ $order->coupon_code }}</div>
                    @endif

                    <div class="order-items">
                        @foreach($order->items as $item)
                            <div class="order-item">
                                {{ $item->quantity }}x <span>{{ $item->product_name }}</span>
                                — ${{ number_format($item->line_total, 0) }}
                                @if($item->notes) <em style="color:#888;">({{ $item->notes }})</em> @endif
                            </div>
                        @endforeach
                    </div>

                    @if($order->payment_proof)
                        <div class="order-proof">
                            <div class="order-proof-label">
                                <i class="fas fa-image"></i> Comprobante de transferencia
                            </div>
                            <span class="order-proof-link" onclick="openProofModal('/storage/{{ $order->payment_proof }}')">
                                <img src="/storage/{{ $order->payment_proof }}" alt="Comprobante" class="order-proof-img"
                                     onerror="this.parentElement.parentElement.style.display='none'">
                            </span>
                        </div>
                    @endif
                </div>

                <div class="order-footer">
                    <div>
                        <div class="order-total">${{ number_format($order->total, 0) }}</div>
                        <div class="order-payment">
                            <i class="fas fa-{{ $order->payment_method === 'efectivo' ? 'money-bill' : ($order->payment_method === 'transferencia' ? 'university' : 'credit-card') }}"></i>
                            {{ $order->payment_label }}
                            @if($order->delivery_fee > 0) | Envío: ${{ number_format($order->delivery_fee, 0) }} @endif
                            @if($order->discount > 0) | Desc: -${{ number_format($order->discount, 0) }} @endif
                        </div>
                    </div>

                    @if($order->status === 'pendiente')
                        <div class="action-buttons">
                            <button class="action-btn btn-accept" onclick="updateStatus({{ $order->id }}, 'aceptado')">
                                <i class="fas fa-check"></i> Aceptar
                            </button>
                            <button class="action-btn btn-reject" onclick="updateStatus({{ $order->id }}, 'cancelado')">
                                <i class="fas fa-times"></i> Rechazar
                            </button>
                        </div>
                    @elseif($order->status === 'aceptado')
                        <div class="action-buttons">
                            <button class="action-btn btn-preparing" onclick="updateStatus({{ $order->id }}, 'en_preparacion')">
                                <i class="fas fa-fire"></i> Preparar
                            </button>
                            <button class="action-btn btn-delivered" onclick="updateStatus({{ $order->id }}, 'entregado')">
                                <i class="fas fa-check-double"></i> Entregado
                            </button>
                            <button class="action-btn btn-reject" onclick="updateStatus({{ $order->id }}, 'cancelado')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    @elseif($order->status === 'en_preparacion')
                        <div class="action-buttons">
                            <button class="action-btn btn-delivered" onclick="updateStatus({{ $order->id }}, 'entregado')">
                                <i class="fas fa-check-double"></i> Entregado
                            </button>
                            <button class="action-btn btn-reject" onclick="updateStatus({{ $order->id }}, 'cancelado')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    @else
                        <div>
                            <select class="status-select" onchange="updateStatus({{ $order->id }}, this.value)">
                                <option value="pendiente" {{ $order->status === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                <option value="aceptado" {{ $order->status === 'aceptado' ? 'selected' : '' }}>Aceptado</option>
                                <option value="en_preparacion" {{ $order->status === 'en_preparacion' ? 'selected' : '' }}>En Preparación</option>
                                <option value="entregado" {{ $order->status === 'entregado' ? 'selected' : '' }}>Entregado</option>
                                <option value="cancelado" {{ $order->status === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                            </select>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="empty-state">
                <i class="fas fa-receipt"></i>
                <p>No hay pedidos aún.</p>
            </div>
        @endforelse

        @if($orders->hasPages())
            <div style="text-align:center; padding:20px;">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    <div class="admin-footer">
        <p>Las Tortas Del Chiche &copy; {{ date('Y') }} — Panel Admin</p>
    </div>

    <div class="toast" id="toast"></div>

    <div class="proof-modal-overlay" id="proofModal" onclick="closeProofModal()">
        <button class="proof-modal-close" onclick="closeProofModal()"><i class="fas fa-times"></i></button>
        <img class="proof-modal-img" id="proofModalImg" src="" alt="Comprobante">
    </div>

    <script>
        function updateStatus(id, status) {
            const card = document.getElementById(`order-${id}`);
            fetch(`/admin/orders/${id}/status`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ status }),
            })
            .then(r => r.json())
            .then(data => {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 600);
            })
            .catch(() => showToast('Error al actualizar', 'error'));
        }

        function showToast(message, type) {
            const toast = document.getElementById('toast');
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            toast.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
            toast.className = `toast ${type} show`;
            setTimeout(() => { toast.className = 'toast'; }, 2500);
        }

        function openProofModal(src) {
            const modal = document.getElementById('proofModal');
            const img = document.getElementById('proofModalImg');
            img.src = src;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeProofModal() {
            const modal = document.getElementById('proofModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeProofModal();
        });
    </script>
    <script src="/js/admin-notify.js"></script>
</body>
</html>
