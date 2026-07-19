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
        'has_mojado',
        'has_seco',
        'has_cochinita',
        'has_lechon',
    ];

    protected $casts = [
        'active'       => 'boolean',
        'has_mojado'   => 'boolean',
        'has_seco'     => 'boolean',
        'has_cochinita' => 'boolean',
        'has_lechon'   => 'boolean',
        'price'        => 'decimal:2',
    ];
}
