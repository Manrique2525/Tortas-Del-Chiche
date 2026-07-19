<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchProductSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();
        $products = Product::all();

        if ($branches->isEmpty() || $products->isEmpty()) return;

        $now = now();
        $data = [];

        foreach ($branches as $branch) {
            foreach ($products as $product) {
                $options = [];
                if ($product->has_mojado) $options['type'][] = 'mojado';
                if ($product->has_seco) $options['type'][] = 'seco';
                if ($product->has_cochinita) $options['meat'][] = 'cochinita';
                if ($product->has_lechon) $options['meat'][] = 'lechon';

                $data[] = [
                    'branch_id' => $branch->id,
                    'product_id' => $product->id,
                    'active' => $product->active,
                    'available_options' => !empty($options) ? json_encode($options) : null,
                    'price_override' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('branch_product')->upsert($data, ['branch_id', 'product_id'], ['active', 'available_options', 'price_override', 'updated_at']);
    }
}
