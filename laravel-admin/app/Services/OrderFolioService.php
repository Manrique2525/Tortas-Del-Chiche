<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderFolioService
{
    private array $prefixes = [
        'atasta'        => 'A',
        'av_universidad' => 'U',
    ];

    public function next(string $branch): array
    {
        $date = now()->toDateString();

        return DB::transaction(function () use ($branch, $date) {
            $last = Order::query()
                ->where('branch', $branch)
                ->whereDate('folio_date', $date)
                ->orderByDesc('folio')
                ->lockForUpdate()
                ->first();

            $folio = $last ? $last->folio + 1 : 1;

            return [
                'folio'      => $folio,
                'folio_date' => $date,
                'display'    => $this->display($branch, $folio),
            ];
        });
    }

    public function display(string $branch, int $folio): string
    {
        $prefix = $this->prefixes[$branch] ?? strtoupper(substr($branch, 0, 1));

        return $prefix . '-' . str_pad((string) $folio, 3, '0', STR_PAD_LEFT);
    }
}
