<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_product')
            ->withPivot(['active', 'available_options', 'price_override'])
            ->withTimestamps();
    }
}
