<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public static function generateScheduleText(array $schedule): string
    {
        $dayNames = [
            'monday' => 'Lunes', 'tuesday' => 'Martes', 'wednesday' => 'Miércoles',
            'thursday' => 'Jueves', 'friday' => 'Viernes', 'saturday' => 'Sábado', 'sunday' => 'Domingo',
        ];
        $days = $schedule['days'] ?? [];
        $open = $schedule['open'] ?? '07:00';
        $close = $schedule['close'] ?? '14:00';

        if (empty($days)) return 'Horario no configurado';

        $allDays = array_keys($dayNames);
        if ($days === $allDays) {
            $dayStr = 'Lunes a Domingo';
        } else {
            $labels = array_map(fn($d) => $dayNames[$d] ?? ucfirst($d), $days);
            $dayStr = implode(', ', $labels);
        }

        $formatTime = fn($t) => date('g:i a', strtotime($t));
        return "$dayStr de {$formatTime($open)} a {$formatTime($close)}";
    }

    protected static function booted(): void
    {
        static::creating(function (self $branch) {
            if (!$branch->key) {
                $branch->key = static::generateUniqueKey($branch->name);
            }
        });

        static::saving(function (self $branch) {
            if (!empty($branch->schedule)) {
                $branch->schedule_text = static::generateScheduleText($branch->schedule);
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

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'branch_product')
            ->withPivot(['active', 'available_options', 'price_override'])
            ->withTimestamps();
    }
}
