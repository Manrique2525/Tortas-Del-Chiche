<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::updateOrCreate(
            ['code' => 'TORTASDELCHICHEJULIO10'],
            ['discount_percent' => 10, 'active' => true]
        );
    }
}
