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
        $today = $this->schedule[$day] ?? null;

        if (!$today || empty($today['open']) || empty($today['close'])) return false;

        $now = now()->format('H:i');
        return $now >= $today['open'] && $now <= $today['close'];
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
