<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'price',
        'image',
        'description',
        'category',
        'active',
        'sort_order',
        'has_type',
        'has_meat',
    ];

    protected $casts = [
        'active'   => 'boolean',
        'has_type' => 'boolean',
        'has_meat' => 'boolean',
        'price'    => 'decimal:2',
    ];
}
