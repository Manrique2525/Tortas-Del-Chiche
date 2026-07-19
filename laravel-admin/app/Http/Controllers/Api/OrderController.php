<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderTotalCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $calculator = new OrderTotalCalculator();
        $serverCalculated = $calculator->calculate(
            $validated['items'],
            (float) ($validated['delivery_fee'] ?? 0),
            $validated['coupon_code'] ?? null
        );

        try {
            $serverCalculated = $calculator->verify($validated, $serverCalculated, 'price');
        } catch (\RuntimeException $e) {
            Log::warning('[OrderController] Price validation blocked', [
                'customer' => $validated['customer_name'],
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $paymentProofPath = null;
        if ($request->hasFile('payment_proof')) {
            $paymentProofPath = $request->file('payment_proof')->store('payment-proofs', 'public');
        }

        $order = DB::transaction(function () use ($validated, $serverCalculated, $paymentProofPath) {
            $order = Order::create([
                'customer_name'    => $validated['customer_name'],
                'customer_phone'   => $validated['customer_phone'],
                'customer_address' => $validated['customer_address'] ?? null,
                'branch'           => $validated['branch'],
                'delivery_type'    => $validated['delivery_type'],
                'payment_method'   => $validated['payment_method'],
                'subtotal'         => $serverCalculated['subtotal'],
                'delivery_fee'     => $serverCalculated['delivery_fee'],
                'discount'         => $serverCalculated['discount'],
                'total'            => $serverCalculated['total'],
                'coupon_code'      => $serverCalculated['coupon_code'],
                'payment_proof'    => $paymentProofPath,
                'status'           => 'pendiente',
            ]);

            foreach ($serverCalculated['items'] as $item) {
                $order->items()->create([
                    'product_id'   => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'line_total'   => $item['line_total'],
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
