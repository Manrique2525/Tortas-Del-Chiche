<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE orders ADD COLUMN payment_proof VARCHAR(500) DEFAULT NULL');
    }

    public function down(): void
    {
        Schema::table('orders', function ($table) {
            $table->dropColumn('payment_proof');
        });
    }
};
