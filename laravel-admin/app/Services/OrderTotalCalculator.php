<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class OrderTotalCalculator
{
    public function calculate(array $items, float $deliveryFee = 0, ?string $couponCode = null, ?string $branch = null, ?string $deliveryType = null, ?float $clientLat = null, ?float $clientLng = null): array
    {
        $verifiedSubtotal = 0;
        $verifiedItems = [];

        $branchPriceMap = [];
        if ($branch) {
            $branchModel = Branch::where('key', $branch)->first();
            if ($branchModel) {
                $branchProducts = $branchModel->products()->get(['products.id', 'branch_product.price_override']);
                foreach ($branchProducts as $bp) {
                    $branchPriceMap[$bp->id] = $bp->pivot->price_override;
                }
            }
        }

        foreach ($items as $item) {
            $product = null;
            if (!empty($item['product_id'])) {
                $product = Product::find($item['product_id']);
                if (!$product) {
                    throw new \RuntimeException('Producto no encontrado en la base de datos. Actualiza tu carrito.');
                }
            }

            $price = (float) ($item['unit_price'] ?? 0);
            if ($product) {
                $productId = $product->id;
                if (isset($branchPriceMap[$productId]) && $branchPriceMap[$productId] !== null) {
                    $price = (float) $branchPriceMap[$productId];
                } else {
                    $price = (float) $product->price;
                }
            }

            $quantity = max(1, min(50, (int) ($item['quantity'] ?? 1)));
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

        $serverDeliveryFee = $this->calculateDeliveryFee($deliveryType, $branch, $clientLat, $clientLng);

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
            $discount = max(0, (int) round($verifiedSubtotal * ($discountPercent / 100)));
        }

        $total = max(0, $verifiedSubtotal + $serverDeliveryFee - $discount);

        return [
            'subtotal'        => $verifiedSubtotal,
            'delivery_fee'    => $serverDeliveryFee,
            'discount'        => $discount,
            'discount_percent' => $discountPercent,
            'total'           => $total,
            'items'           => $verifiedItems,
            'coupon_code'     => $coupon ? $coupon->code : null,
            'coupon_valid'    => $coupon !== null,
        ];
    }

    private function calculateDeliveryFee(?string $deliveryType, ?string $branchKey, ?float $clientLat, ?float $clientLng): float
    {
        if ($deliveryType === 'recoger' || !$branchKey) {
            return 0;
        }

        $branch = Branch::where('key', $branchKey)->first();
        if (!$branch || !$branch->lat || !$branch->lng || !$clientLat || !$clientLng) {
            return 40.0;
        }

        $dist = $this->haversineDistance((float) $branch->lat, (float) $branch->lng, $clientLat, $clientLng);
        $baseFee = 40.0;
        $baseKm = 4;
        $perKm = 10.0;
        $minFee = 40.0;
        $maxFee = 120.0;

        $extra = max(0, ceil($dist) - $baseKm);
        return min($maxFee, max($minFee, $baseFee + $extra * $perKm));
    }

    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function verify(array $clientCalculated, array $serverCalculated, string $context = 'order'): array
    {
        $mode = config("security.price_recalculation", 'log');

        $discrepancies = [];
        foreach (['subtotal', 'discount'] as $field) {
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
