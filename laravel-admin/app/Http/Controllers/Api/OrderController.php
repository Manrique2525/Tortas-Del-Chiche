<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name'   => 'required|string|max:255',
            'customer_phone'  => 'required|string|max:20',
            'customer_address'=> 'nullable|string|max:500',
            'branch'          => 'required|string|exists:sucursales,key',
            'delivery_type'   => 'required|in:domicilio,recoger',
            'payment_method'  => 'required|in:efectivo,transferencia,mercadopago',
            'subtotal'        => 'required|numeric|min:0',
            'delivery_fee'    => 'nullable|numeric|min:0',
            'discount'        => 'nullable|numeric|min:0',
            'total'           => 'required|numeric|min:0',
            'coupon_code'     => 'nullable|string|max:50',
            'items'           => 'required|array|min:1',
            'items.*.product_id'   => 'nullable|integer',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.options'      => 'nullable|array',
            'payment_proof'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $paymentProofPath = null;
        if ($request->hasFile('payment_proof')) {
            $paymentProofPath = $request->file('payment_proof')->store('payment-proofs', 'public');
        }

        $order = DB::transaction(function () use ($validated, $paymentProofPath) {
            $order = Order::create([
                'customer_name'    => $validated['customer_name'],
                'customer_phone'   => $validated['customer_phone'],
                'customer_address' => $validated['customer_address'] ?? null,
                'branch'           => $validated['branch'],
                'delivery_type'    => $validated['delivery_type'],
                'payment_method'   => $validated['payment_method'],
                'subtotal'         => $validated['subtotal'],
                'delivery_fee'     => $validated['delivery_fee'] ?? 0,
                'discount'         => $validated['discount'] ?? 0,
                'total'            => $validated['total'],
                'coupon_code'      => $validated['coupon_code'] ?? null,
                'payment_proof'    => $paymentProofPath,
                'status'           => 'pendiente',
            ]);

            foreach ($validated['items'] as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $order->items()->create([
                    'product_id'   => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'line_total'   => $lineTotal,
                    'options'      => $item['options'] ?? null,
                ]);
            }

            return $order;
        });

        return response()->json([
            'success' => true,
            'order'   => [
                'id'     => $order->id,
                'status' => $order->status_label,
                'total'  => number_format($order->total, 2),
            ],
            'message' => 'Pedido registrado correctamente',
        ], 201);
    }
}
