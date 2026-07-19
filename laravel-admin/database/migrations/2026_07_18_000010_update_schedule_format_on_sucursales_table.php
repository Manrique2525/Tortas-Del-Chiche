<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $branches = DB::table('sucursales')->get();
        foreach ($branches as $branch) {
            $schedule = json_decode($branch->schedule, true);
            if ($schedule && isset($schedule['monday'])) {
                $open = $schedule['monday']['open'] ?? '07:00';
                $close = $schedule['monday']['close'] ?? '14:00';
                $days = array_keys($schedule);
                DB::table('sucursales')->where('id', $branch->id)->update([
                    'schedule' => json_encode([
                        'open' => $open,
                        'close' => $close,
                        'days' => $days,
                    ]),
                ]);
            }
        }
    }

    public function down(): void
    {
        $branches = DB::table('sucursales')->get();
        foreach ($branches as $branch) {
            $schedule = json_decode($branch->schedule, true);
            if ($schedule && isset($schedule['open'])) {
                $days = $schedule['days'] ?? ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
                $perDay = [];
                foreach ($days as $day) {
                    $perDay[$day] = [
                        'open' => $schedule['open'],
                        'close' => $schedule['close'],
                    ];
                }
                DB::table('sucursales')->where('id', $branch->id)->update([
                    'schedule' => json_encode($perDay),
                ]);
            }
        }
    }
};
