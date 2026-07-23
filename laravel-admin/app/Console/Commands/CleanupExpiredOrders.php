<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class CleanupExpiredOrders extends Command
{
    protected $signature = 'orders:cleanup-expired';

    protected $description = 'Cancel orders with MercadoPago payment that remain pending for more than 45 minutes';

    public function handle(): int
    {
        $cutoff = now()->subMinutes(45);

        $cancelled = Order::where('payment_method', 'mercadopago')
            ->where('status', 'pendiente')
            ->where('created_at', '<', $cutoff)
            ->update(['status' => 'cancelado']);

        if ($cancelled > 0) {
            $this->info("Cancelled {$cancelled} expired MercadoPago order(s).");
        } else {
            $this->info("No expired orders found.");
        }

        return Command::SUCCESS;
    }
}
