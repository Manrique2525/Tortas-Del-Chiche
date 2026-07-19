<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;

class MercadoPagoController extends Controller
{
    private function initSDK(): void
    {
        $token = Config::get('services.mercadopago.access_token');
        if (!$token) {
            throw new \RuntimeException('MERCADO_PAGO_ACCESS_TOKEN no configurado');
        }
        MercadoPagoConfig::setAccessToken($token);
    }

    public function test(): JsonResponse
    {
        try {
            $token = Config::get('services.mercadopago.access_token');
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'MERCADO_PAGO_ACCESS_TOKEN no está configurado en el .env',
                ]);
            }
            $this->initSDK();
            return response()->json([
                'success' => true,
                'message' => 'SDK de Mercado Pago v3 funcionando correctamente',
                'token_prefix' => substr($token, 0, 12) . '...',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al inicializar SDK: ' . $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
        }
    }

    public function createPreference(Request $request): JsonResponse
    {
        try {
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
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Datos inválidos: ' . $e->getMessage()], 422);
        }

        try {
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
        } catch (\Exception $e) {
            Log::error('[MercadoPago] Error al crear orden: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al registrar el pedido'], 500);
        }

        try {
            $this->initSDK();

            $mpItems = [];
            foreach ($validated['items'] as $item) {
                $mpItems[] = [
                    'title'      => $item['product_name'],
                    'quantity'   => (int) $item['quantity'],
                    'unit_price' => (float) $item['unit_price'],
                    'currency_id'=> 'MXN',
                ];
            }

            if (($validated['delivery_fee'] ?? 0) > 0) {
                $mpItems[] = [
                    'title'      => 'Costo de envío',
                    'quantity'   => 1,
                    'unit_price' => (float) $validated['delivery_fee'],
                    'currency_id'=> 'MXN',
                ];
            }

            $preferenceData = [
                'items'               => $mpItems,
                'payer'               => [
                    'name'  => $validated['customer_name'],
                    'phone' => ['number' => $validated['customer_phone']],
                ],
                'external_reference'  => (string) $order->id,
                'back_urls'           => [
                    'success' => 'https://tortas-del-chiche.onrender.com/?mp_status=success&order_id=' . $order->id,
                    'failure' => 'https://tortas-del-chiche.onrender.com/?mp_status=failure&order_id=' . $order->id,
                    'pending' => 'https://tortas-del-chiche.onrender.com/?mp_status=pending&order_id=' . $order->id,
                ],
                'auto_return'         => 'approved',
                'binary_mode'         => true,
                'statement_descriptor'=> 'TORTAS DEL CHICHE',
                'notification_url'    => 'https://tortas-del-chiche.onrender.com/api/mercadopago/webhook',
                'expires'             => true,
                'expiration_date_to'  => now()->addMinutes(30)->format('c'),
            ];

            $client = new PreferenceClient();
            $preference = $client->create($preferenceData);

            if (!$preference || !$preference->id) {
                Log::error('[MercadoPago] Preferencia no creada (sin ID)');
                return response()->json(['success' => false, 'message' => 'Mercado Pago no devolvió una preferencia válida'], 500);
            }

            $order->update(['mp_preference_id' => $preference->id]);

            $isSandbox = Config::get('services.mercadopago.env') === 'test';
            $checkoutUrl = $isSandbox ? $preference->sandbox_init_point : $preference->init_point;

            return response()->json([
                'success'       => true,
                'order_id'      => $order->id,
                'preference_id' => $preference->id,
                'init_point'    => $checkoutUrl,
            ]);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (method_exists($e, 'getApiResponse')) {
                $apiRes = $e->getApiResponse();
                $msg .= ' | API: ' . ($apiRes ? json_encode($apiRes->getContent()) : 'sin respuesta');
            }
            Log::error('[MercadoPago] Excepción: ' . $msg . "\n" . $e->getTraceAsString());
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
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
            $this->initSDK();
            $client = new PaymentClient();
            $payment = $client->get((int) $paymentId);

            if (!$payment) {
                return response()->json(['received' => true]);
            }

            $orderId = (int) ($payment->external_reference ?? 0);
            $order = Order::find($orderId);
            if (!$order) {
                return response()->json(['received' => true]);
            }

            $order->mp_payment_id = (string) $paymentId;

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
            Log::error('[MercadoPago Webhook] Error: ' . $e->getMessage());
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
            'success'       => true,
            'order_id'      => $order->id,
            'status'        => $order->status,
            'status_label'  => $order->status_label,
            'mp_payment_id' => $order->mp_payment_id,
        ]);
    }
}
