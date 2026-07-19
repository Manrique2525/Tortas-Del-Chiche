<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use MercadoPago\SDK;
use MercadoPago\Preference;
use MercadoPago\Item;
use MercadoPago\Payment;

class MercadoPagoController extends Controller
{
    public function __construct()
    {
        SDK::setAccessToken(Config::get('services.mercadopago.access_token'));
    }

    public function createPreference(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name'    => 'required|string|max:255',
            'customer_phone'   => 'required|string|max:20',
            'customer_address' => 'nullable|string|max:500',
            'branch'           => 'required|string|exists:sucursales,key',
            'delivery_type'    => 'required|in:domicilio,recoger',
            'subtotal'         => 'required|numeric|min:0',
            'delivery_fee'     => 'nullable|numeric|min:0',
            'discount'         => 'nullable|numeric|min:0',
            'total'            => 'required|numeric|min:0',
            'coupon_code'      => 'nullable|string|max:50',
            'items'            => 'required|array|min:1',
            'items.*.product_id'   => 'nullable|integer',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.options'      => 'nullable|array',
        ]);

        $order = DB::transaction(function () use ($validated) {
            $order = Order::create([
                'customer_name'    => $validated['customer_name'],
                'customer_phone'   => $validated['customer_phone'],
                'customer_address' => $validated['customer_address'] ?? null,
                'branch'           => $validated['branch'],
                'delivery_type'    => $validated['delivery_type'],
                'payment_method'   => 'mercadopago',
                'subtotal'         => $validated['subtotal'],
                'delivery_fee'     => $validated['delivery_fee'] ?? 0,
                'discount'         => $validated['discount'] ?? 0,
                'total'            => $validated['total'],
                'coupon_code'      => $validated['coupon_code'] ?? null,
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

        $preference = new Preference();

        $mpItems = [];
        foreach ($validated['items'] as $item) {
            $mpItem = new Item();
            $mpItem->title = $item['product_name'];
            $mpItem->quantity = (int) $item['quantity'];
            $mpItem->unit_price = (float) $item['unit_price'];
            $mpItem->currency_id = 'MXN';
            $mpItems[] = $mpItem;
        }

        if ($validated['delivery_fee'] > 0) {
            $feeItem = new Item();
            $feeItem->title = 'Costo de envío';
            $feeItem->quantity = 1;
            $feeItem->unit_price = (float) $validated['delivery_fee'];
            $feeItem->currency_id = 'MXN';
            $mpItems[] = $feeItem;
        }

        $preference->items = $mpItems;

        $preference->payer = [
            'name' => $validated['customer_name'],
            'phone' => [ 'number' => $validated['customer_phone'] ],
        ];

        $preference->external_reference = (string) $order->id;
        $preference->back_urls = [
            'success' => 'https://tortas-del-chiche.onrender.com/?mp_status=success&order_id=' . $order->id,
            'failure' => 'https://tortas-del-chiche.onrender.com/?mp_status=failure&order_id=' . $order->id,
            'pending' => 'https://tortas-del-chiche.onrender.com/?mp_status=pending&order_id=' . $order->id,
        ];
        $preference->auto_return = 'approved';
        $preference->binary_mode = true;
        $preference->statement_descriptor = 'TORTAS DEL CHICHE';
        $preference->notification_url = 'https://tortas-del-chiche.onrender.com/api/mercadopago/webhook';
        $preference->expires = true;
        $preference->expiration_date_to = now()->addMinutes(30)->format('c');

        $preference->save();

        if (!$preference->id) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la preferencia en Mercado Pago',
            ], 500);
        }

        $order->update([
            'mp_preference_id' => $preference->id,
        ]);

        return response()->json([
            'success'       => true,
            'order_id'      => $order->id,
            'preference_id' => $preference->id,
            'init_point'    => $preference->init_point,
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $topic = $request->input('topic') ?: $request->input('type');

        if ($topic !== 'payment') {
            return response()->json(['received' => true]);
        }

        $paymentId = $request->input('data.id') ?: $request->input('id');

        if (!$paymentId) {
            return response()->json(['received' => true]);
        }

        try {
            $payment = Payment::find_by_id($paymentId);

            if (!$payment) {
                return response()->json(['received' => true]);
            }

            $orderId = (int) $payment->external_reference;
            $order = Order::find($orderId);

            if (!$order) {
                return response()->json(['received' => true]);
            }

            $order->mp_payment_id = $paymentId;

            switch ($payment->status) {
                case 'approved':
                    $order->status = 'pagado';
                    break;
                case 'rejected':
                case 'cancelled':
                    $order->status = 'cancelado';
                    break;
                case 'refunded':
                case 'charged_back':
                    $order->status = 'reembolsado';
                    break;
                default:
                    $order->status = 'pendiente';
            }

            $order->save();
        } catch (\Exception $e) {
            // Log error but return 200 to MP
        }

        return response()->json(['received' => true]);
    }

    public function getPaymentStatus(Request $request): JsonResponse
    {
        $orderId = $request->input('order_id');
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Orden no encontrada'], 404);
        }

        return response()->json([
            'success'  => true,
            'order_id' => $order->id,
            'status'   => $order->status,
            'status_label' => $order->status_label,
            'mp_payment_id' => $order->mp_payment_id,
        ]);
    }
}
