<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Branch extends Model
{
    protected $table = 'sucursales';

    protected $fillable = [
        'key', 'name', 'address', 'phone', 'whatsapp',
        'schedule', 'schedule_text', 'latitude', 'longitude',
        'didi_url', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'schedule' => 'array',
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function getIsOpenAttribute(): bool
    {
        if (!$this->is_active) return false;

        $day = strtolower(now()->format('l'));
        $days = $this->schedule['days'] ?? [];

        if (!in_array($day, $days)) return false;

        $open = $this->schedule['open'] ?? '07:00';
        $close = $this->schedule['close'] ?? '14:00';

        $now = now()->format('H:i');
        return $now >= $open && $now <= $close;
    }

    protected static function booted(): void
    {
        static::creating(function (self $branch) {
            if (!$branch->key) {
                $branch->key = static::generateUniqueKey($branch->name);
            }
        });
    }

    public static function generateUniqueKey(string $name): string
    {
        $base = Str::slug($name);
        $key = $base;
        $i = 1;
        while (static::where('key', $key)->exists()) {
            $key = $base . '-' . $i++;
        }
        return $key;
    }
}
