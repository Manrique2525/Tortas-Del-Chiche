<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['has_type', 'has_meat']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('has_mojado')->default(false)->after('active');
            $table->boolean('has_seco')->default(false)->after('has_mojado');
            $table->boolean('has_cochinita')->default(false)->after('has_seco');
            $table->boolean('has_lechon')->default(false)->after('has_cochinita');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['has_mojado', 'has_seco', 'has_cochinita', 'has_lechon']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('has_type')->default(false)->after('active');
            $table->boolean('has_meat')->default(false)->after('has_type');
        });
    }
};
