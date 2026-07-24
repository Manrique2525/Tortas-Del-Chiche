<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderTotalCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeController extends Controller
{
    private function initStripe(): void
    {
        $secret = Config::get('services.stripe.secret');
        if (!$secret) {
            throw new \RuntimeException('STRIPE_SECRET no configurado');
        }
        Stripe::setApiKey($secret);
    }

    public function createCheckoutSession(Request $request): JsonResponse
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

        $calculator = new OrderTotalCalculator();
        $serverCalculated = $calculator->calculate(
            $validated['items'],
            (float) ($validated['delivery_fee'] ?? 0),
            $validated['coupon_code'] ?? null
        );

        try {
            $serverCalculated = $calculator->verify($validated, $serverCalculated, 'price');
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        try {
            $order = DB::transaction(function () use ($validated, $serverCalculated) {
                $order = Order::create([
                    'customer_name'    => $validated['customer_name'],
                    'customer_phone'   => $validated['customer_phone'],
                    'customer_address' => $validated['customer_address'] ?? null,
                    'branch'           => $validated['branch'],
                    'delivery_type'    => $validated['delivery_type'],
                    'payment_method'   => 'stripe',
                    'subtotal'         => $serverCalculated['subtotal'],
                    'delivery_fee'     => $serverCalculated['delivery_fee'],
                    'discount'         => $serverCalculated['discount'],
                    'total'            => $serverCalculated['total'],
                    'coupon_code'      => $serverCalculated['coupon_code'],
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
        } catch (\Exception $e) {
            Log::error('[Stripe] Error al crear orden: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al registrar el pedido'], 500);
        }

        try {
            $this->initStripe();

            $lineItems = [];
            foreach ($serverCalculated['items'] as $item) {
                $lineItems[] = [
                    'price_data' => [
                        'currency'     => 'mxn',
                        'product_data' => [
                            'name' => $item['product_name'],
                        ],
                        'unit_amount'  => (int) round($item['unit_price'] * 100),
                    ],
                    'quantity' => (int) $item['quantity'],
                ];
            }

            if ($serverCalculated['delivery_fee'] > 0) {
                $lineItems[] = [
                    'price_data' => [
                        'currency'     => 'mxn',
                        'product_data' => [
                            'name' => 'Costo de envío',
                        ],
                        'unit_amount'  => (int) round($serverCalculated['delivery_fee'] * 100),
                    ],
                    'quantity' => 1,
                ];
            }

            $appUrl = config('app.url', 'https://tortas-del-chiche.onrender.com');

            $sessionParams = [
                'payment_method_types' => ['card'],
                'line_items'           => $lineItems,
                'mode'                 => 'payment',
                'success_url'          => $appUrl . '/?stripe_status=success&order_id=' . $order->id . '&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'           => $appUrl . '/?stripe_status=cancel&order_id=' . $order->id,
                'customer_creation'    => 'always',
                'client_reference_id'  => (string) $order->id,
                'metadata'             => [
                    'order_id'      => $order->id,
                    'customer_name' => $validated['customer_name'],
                    'customer_phone'=> $validated['customer_phone'],
                ],
            ];

            $session = CheckoutSession::create($sessionParams);

            $order->update(['stripe_session_id' => $session->id]);

            return response()->json([
                'success'    => true,
                'order_id'   => $order->id,
                'session_id' => $session->id,
                'url'        => $session->url,
            ]);
        } catch (ApiErrorException $e) {
            Log::error('[Stripe] API Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al conectar con Stripe: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error('[Stripe] Excepción: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('stripe-signature');

        $webhookSecret = Config::get('services.stripe.webhook_secret');
        if (!$webhookSecret) {
            Log::warning('[Stripe Webhook] No STRIPE_WEBHOOK_SECRET configurado');
            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            Log::warning('[Stripe Webhook] Firma inválida: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            $orderId = (int) ($session->client_reference_id ?? 0);
            $order = Order::find($orderId);

            if (!$order) {
                Log::warning('[Stripe Webhook] Orden no encontrada: ' . $orderId);
                return response()->json(['received' => true]);
            }

            $stripePaymentIntent = $session->payment_intent ?? null;

            if ($order->stripe_payment_intent_id === $stripePaymentIntent && $order->status === 'pagado') {
                return response()->json(['received' => true]);
            }

            if ($session->payment_status === 'paid') {
                $order->status = 'pagado';
                $order->stripe_payment_intent_id = $stripePaymentIntent;
                $order->save();

                Log::info('[Stripe Webhook] Pago confirmado para orden #' . $orderId);
            } elseif ($session->payment_status === 'unpaid') {
                $order->status = 'pendiente';
                $order->save();
            }
        }

        if ($event->type === 'checkout.session.expired') {
            $session = $event->data->object;

            $orderId = (int) ($session->client_reference_id ?? 0);
            $order = Order::find($orderId);

            if ($order && $order->status === 'pendiente') {
                $order->update(['status' => 'cancelado']);
                Log::info('[Stripe Webhook] Orden #' . $orderId . ' cancelada por expiración de sesión');
            }
        }

        return response()->json(['received' => true]);
    }

    public function cancelOrder(Request $request): JsonResponse
    {
        $orderId = $request->input('order_id');

        if (!$orderId) {
            return response()->json(['success' => false, 'message' => 'order_id requerido'], 422);
        }

        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Orden no encontrada'], 404);
        }

        if ($order->payment_method !== 'stripe' || $order->status !== 'pendiente') {
            return response()->json(['success' => true, 'message' => 'Orden ya procesada']);
        }

        $order->update(['status' => 'cancelado']);

        Log::info('[Stripe] Orden #' . $orderId . ' cancelada por el usuario');

        return response()->json(['success' => true]);
    }

    public function getPaymentStatus(Request $request): JsonResponse
    {
        $orderId = $request->input('order_id');
        $sessionId = $request->input('session_id');

        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Orden no encontrada'], 404);
        }

        if ($order->status === 'pendiente' && $sessionId) {
            try {
                $this->initStripe();
                $session = CheckoutSession::retrieve($sessionId);

                if ($session->payment_status === 'paid') {
                    $order->status = 'pagado';
                    $order->stripe_payment_intent_id = $session->payment_intent ?? null;
                    $order->save();
                }
            } catch (\Exception $e) {
                Log::warning('[Stripe] Error al verificar sesión: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success'      => true,
            'order_id'     => $order->id,
            'status'       => $order->status,
            'status_label' => $order->status_label,
        ]);
    }
}
