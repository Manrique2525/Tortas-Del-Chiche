<?php

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->boolean('active')->default(true);
            $table->json('available_options')->nullable();
            $table->decimal('price_override', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'product_id']);
        });

        $branches = Branch::all();
        $products = Product::all();

        if ($branches->isNotEmpty() && $products->isNotEmpty()) {
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

            DB::table('branch_product')->insert($data);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_product');
    }
};
