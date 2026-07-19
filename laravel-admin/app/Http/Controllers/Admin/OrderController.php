<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('items')->latest();

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
                  ->orWhere('id', $search);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->paginate(20)->withQueryString();

        $todayStats = Order::whereDate('created_at', today())
                            ->where('status', '!=', 'cancelado')
                            ->selectRaw('COALESCE(SUM(subtotal) - SUM(discount), 0) as ingresos')
                            ->selectRaw('COALESCE(SUM(delivery_fee), 0) as envios')
                            ->first();

        $stats = [
            'total'       => Order::count(),
            'pendiente'   => Order::where('status', 'pendiente')->count(),
            'aceptado'    => Order::where('status', 'aceptado')->count(),
            'hoy'         => Order::whereDate('created_at', today())->count(),
            'ingresos_hoy'=> $todayStats->ingresos,
            'envios_hoy'  => $todayStats->envios,
        ];

        return view('admin.orders.index', compact('orders', 'stats'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pendiente,aceptado,en_preparacion,entregado,cancelado',
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
