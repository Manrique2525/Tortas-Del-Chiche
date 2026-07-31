<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('folio')->nullable()->after('id');
            $table->date('folio_date')->nullable()->after('folio');
        });

        $this->backfill();

        Schema::table('orders', function (Blueprint $table) {
            $table->unique(['branch', 'folio_date', 'folio']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['branch', 'folio_date', 'folio']);
            $table->dropColumn(['folio', 'folio_date']);
        });
    }

    private function backfill(): void
    {
        $groups = DB::table('orders')
            ->select('branch', DB::raw('DATE(created_at) as order_date'))
            ->groupBy('branch', 'order_date')
            ->orderBy('branch')
            ->orderBy('order_date')
            ->get();

        foreach ($groups as $group) {
            $orders = DB::table('orders')
                ->where('branch', $group->branch)
                ->whereDate('created_at', $group->order_date)
                ->orderBy('id')
                ->get();

            $counter = 0;
            foreach ($orders as $order) {
                $counter++;
                DB::table('orders')
                    ->where('id', $order->id)
                    ->update([
                        'folio'      => $counter,
                        'folio_date' => $group->order_date,
                    ]);
            }
        }
    }
};
