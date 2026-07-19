<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookValidator
{
    public function validateSignature(Request $request): bool
    {
        $mode = config('security.webhook_signature_check', 'off');
        if ($mode === 'off') {
            return true;
        }

        $signature = $request->header('x-signature');
        $requestId = $request->header('x-request-id');

        if (!$signature || !$requestId) {
            Log::warning('[MercadoPago] Missing signature headers');
            return $mode !== 'block';
        }

        $parts = explode(',', $signature);
        $ts = null;
        $v1 = null;
        foreach ($parts as $part) {
            if (str_starts_with($part, 'ts=')) {
                $ts = substr($part, 3);
            } elseif (str_starts_with($part, 'v1=')) {
                $v1 = substr($part, 3);
            }
        }

        if (!$ts || !$v1) {
            Log::warning('[MercadoPago] Invalid signature format');
            return $mode !== 'block';
        }

        $webhookSecret = config('services.mercadopago.webhook_secret');
        if (!$webhookSecret) {
            Log::warning('[MercadoPago] Webhook secret not configured');
            return $mode !== 'block';
        }

        $dataId = $request->input('data.id') ?: $request->input('id');
        $manifest = "{$ts}|{$dataId}";
        $expected = hash_hmac('sha256', $manifest, $webhookSecret);

        if ($expected !== $v1) {
            Log::warning('[MercadoPago] Invalid webhook signature', [
                'expected' => $expected,
                'received' => $v1,
                'data_id' => $dataId,
            ]);
            return $mode !== 'block';
        }

        return true;
    }

    public function validateAmount(float $paymentAmount, float $orderTotal): bool
    {
        $mode = config('security.webhook_amount_check', 'off');
        if ($mode === 'off') {
            return true;
        }

        $diff = abs($paymentAmount - $orderTotal);
        if ($diff > 0.01) {
            Log::warning('[MercadoPago] Amount mismatch', [
                'payment' => $paymentAmount,
                'order' => $orderTotal,
            ]);
            return $mode !== 'block';
        }

        return true;
    }
}
