<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_phone', 20)->nullable();
            $table->text('customer_address')->nullable();
            $table->string('branch', 50)->default('atasta');
            $table->enum('delivery_type', ['domicilio', 'recoger'])->default('domicilio');
            $table->enum('payment_method', ['efectivo', 'transferencia', 'mercadopago'])->default('efectivo');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->enum('status', ['pendiente', 'en_preparacion', 'entregado', 'cancelado'])->default('pendiente');
            $table->string('coupon_code', 50)->nullable();
            $table->string('mp_payment_id', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
