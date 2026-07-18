<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'discount_percent',
        'active',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'active' => 'boolean',
    ];
}
