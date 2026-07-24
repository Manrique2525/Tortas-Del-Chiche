<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('mp_payment_id', 'stripe_payment_intent_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('mp_preference_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('stripe_session_id', 100)->nullable()->after('stripe_payment_intent_id');
        });

        DB::table('orders')
            ->where('payment_method', 'mercadopago')
            ->update(['payment_method' => 'stripe']);
    }

    public function down(): void
    {
        DB::table('orders')
            ->where('payment_method', 'stripe')
            ->update(['payment_method' => 'mercadopago']);

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('stripe_session_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('mp_preference_id', 100)->nullable()->after('stripe_payment_intent_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('stripe_payment_intent_id', 'mp_payment_id');
        });
    }
};
