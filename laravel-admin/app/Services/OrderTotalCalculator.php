<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class OrderTotalCalculator
{
    public function calculate(array $items, float $deliveryFee = 0, ?string $couponCode = null): array
    {
        $verifiedSubtotal = 0;
        $verifiedItems = [];

        foreach ($items as $item) {
            $product = null;
            if (!empty($item['product_id'])) {
                $product = Product::find($item['product_id']);
            }

            $price = $product ? (float) $product->price : (float) ($item['unit_price'] ?? 0);
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $lineTotal = $price * $quantity;

            $verifiedSubtotal += $lineTotal;

            $verifiedItems[] = [
                'product_id'   => $item['product_id'] ?? null,
                'product_name' => $product ? $product->name : ($item['product_name'] ?? ''),
                'quantity'     => $quantity,
                'unit_price'   => $price,
                'line_total'   => $lineTotal,
                'options'      => $item['options'] ?? null,
            ];
        }

        $coupon = null;
        if ($couponCode) {
            $coupon = Coupon::where('code', strtoupper(trim($couponCode)))
                ->where('active', true)
                ->first();
        }

        $discount = 0;
        $discountPercent = 0;
        if ($coupon) {
            $discountPercent = (float) $coupon->discount_percent;
            $discount = (int) round($verifiedSubtotal * ($discountPercent / 100));
        }

        $total = max(0, $verifiedSubtotal + $deliveryFee - $discount);

        return [
            'subtotal'        => $verifiedSubtotal,
            'delivery_fee'    => $deliveryFee,
            'discount'        => $discount,
            'discount_percent' => $discountPercent,
            'total'           => $total,
            'items'           => $verifiedItems,
            'coupon_code'     => $coupon ? $coupon->code : null,
            'coupon_valid'    => $coupon !== null,
        ];
    }

    public function verify(array $clientCalculated, array $serverCalculated, string $context = 'order'): array
    {
        $mode = config("security.{$context}_validation", 'log');

        $discrepancies = [];
        foreach (['subtotal', 'delivery_fee', 'discount', 'total'] as $field) {
            $client = (float) ($clientCalculated[$field] ?? 0);
            $server = (float) ($serverCalculated[$field] ?? 0);
            if (abs($client - $server) > 0.01) {
                $discrepancies[$field] = ['client' => $client, 'server' => $server];
            }
        }

        if (!empty($discrepancies)) {
            Log::warning("[PriceValidation] {$context} discrepancies", [
                'discrepancies' => $discrepancies,
                'mode' => $mode,
            ]);

            if ($mode === 'block') {
                throw new \RuntimeException('Error de validación de precios. Contacta al administrador.');
            }
        }

        return $serverCalculated;
    }
}
