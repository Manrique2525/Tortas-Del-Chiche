<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->filled('date_from') ? $request->input('date_from') : today()->toDateString();
        $dateTo = $request->filled('date_to') ? $request->input('date_to') : today()->toDateString();

        $query = Order::with('items')->latest();
        $this->applyFilters($query, $request, $dateFrom, $dateTo);

        $orders = $query->paginate(20)->withQueryString();

        $statsQuery = Order::query();
        $this->applyFilters($statsQuery, $request, $dateFrom, $dateTo);

        $totalPedidos = (clone $statsQuery)->count();

        $pendientes = (clone $statsQuery)->where('status', 'pendiente')->count();

        $aceptados = (clone $statsQuery)->where('status', 'aceptado')->count();

        $earnedQuery = (clone $statsQuery)->where('status', '!=', 'cancelado');

        $ingresos = (clone $earnedQuery)
            ->selectRaw('COALESCE(SUM(subtotal) - SUM(discount), 0) as total')
            ->value('total');

        $envios = (clone $earnedQuery)
            ->selectRaw('COALESCE(SUM(delivery_fee), 0) as total')
            ->value('total');

        $stats = [
            'total'     => $totalPedidos,
            'pendiente' => $pendientes,
            'aceptado'  => $aceptados,
            'ingresos'  => $ingresos,
            'envios'    => $envios,
            'vendido'   => $ingresos,
        ];

        $branches = Branch::orderBy('sort_order')->get();

        return view('admin.orders.index', compact('orders', 'stats', 'branches'));
    }

    private function applyFilters($query, Request $request, $dateFrom, $dateTo)
    {
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('branch')) {
            $query->where('branch', $request->branch);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('id', $search)
                  ->orWhere('folio', $search);
            });
        }

        $query->whereDate('created_at', '>=', $dateFrom)
              ->whereDate('created_at', '<=', $dateTo);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pendiente,aceptado,en_preparacion,entregado,cancelado,pagado,reembolsado',
        ]);

        $order->update(['status' => $request->status]);

        return response()->json([
            'status'         => $order->status,
            'status_label'   => $order->status_label,
            'status_color'   => $order->status_color,
            'message'        => 'Estado actualizado',
            'customer_name'  => $order->customer_name,
            'customer_phone' => $order->customer_phone,
        ]);
    }
}
