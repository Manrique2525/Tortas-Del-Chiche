<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'customer_name',
        'customer_phone',
        'customer_address',
        'branch',
        'delivery_type',
        'payment_method',
        'subtotal',
        'delivery_fee',
        'discount',
        'total',
        'status',
        'coupon_code',
        'mp_payment_id',
        'mp_preference_id',
        'payment_proof',
    ];

    protected $casts = [
        'subtotal'    => 'decimal:2',
        'delivery_fee'=> 'decimal:2',
        'discount'    => 'decimal:2',
        'total'       => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pendiente'      => 'Pendiente',
            'aceptado'       => 'Aceptado',
            'en_preparacion' => 'En Preparación',
            'entregado'      => 'Entregado',
            'cancelado'      => 'Cancelado',
            'pagado'         => 'Pagado',
            'reembolsado'    => 'Reembolsado',
            default          => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pendiente'      => '#f39c12',
            'aceptado'       => '#2196F3',
            'en_preparacion' => '#3498db',
            'entregado'      => '#27ae60',
            'cancelado'      => '#e74c3c',
            'pagado'         => '#2ecc71',
            'reembolsado'    => '#e74c3c',
            default          => '#888',
        };
    }

    public function getPaymentLabelAttribute(): string
    {
        return match($this->payment_method) {
            'efectivo'      => 'Efectivo',
            'transferencia' => 'Transferencia',
            'mercadopago'   => 'Tarjeta',
            default         => ucfirst($this->payment_method),
        };
    }
}
