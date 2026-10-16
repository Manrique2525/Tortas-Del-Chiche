<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RouteDistanceService
{
    private const OSRM_URL = 'https://router.project-osrm.org/route/v1/driving/%F,%F;%F,%F?overview=false';
    private const TIMEOUT_SECONDS = 3;
    private const CACHE_MINUTES = 60;

    /**
     * Distancia entre dos puntos.
     *
     * Intenta obtener la distancia por calles (OSRM/OpenStreetMap).
     * Si la API falla o tarda, usa Haversine (línea recta) como respaldo.
     *
     * @return array{km: float, source: string} source: 'route' | 'haversine'
     */
    public function getDistance(float $fromLat, float $fromLng, float $toLat, float $toLng): array
    {
        $routeKm = $this->getRouteDistance($fromLat, $fromLng, $toLat, $toLng);

        if ($routeKm !== null) {
            return ['km' => $routeKm, 'source' => 'route'];
        }

        return [
            'km' => $this->haversineDistance($fromLat, $fromLng, $toLat, $toLng),
            'source' => 'haversine',
        ];
    }

    /**
     * Distancia por calles vía OSRM. Devuelve null si no está disponible.
     */
    public function getRouteDistance(float $fromLat, float $fromLng, float $toLat, float $toLng): ?float
    {
        if (!$this->isValidCoordinate($fromLat, $fromLng) || !$this->isValidCoordinate($toLat, $toLng)) {
            return null;
        }

        $cacheKey = sprintf('route_distance:%.5f,%.5f,%.5f,%.5f', $fromLat, $fromLng, $toLat, $toLng);
        $cached = Cache::get($cacheKey);
        if (is_numeric($cached)) {
            return (float) $cached;
        }

        try {
            $url = sprintf(self::OSRM_URL, $fromLng, $fromLat, $toLng, $toLat);

            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->connectTimeout(self::TIMEOUT_SECONDS)
                ->get($url);

            if (!$response->successful()) {
                Log::warning('[RouteDistance] OSRM respondió con error', ['status' => $response->status()]);
                return null;
            }

            $meters = $response->json('routes.0.distance');
            if (!is_numeric($meters)) {
                return null;
            }

            $km = round(((float) $meters) / 1000, 2);
            if ($km <= 0) {
                return null;
            }

            Cache::put($cacheKey, $km, now()->addMinutes(self::CACHE_MINUTES));

            return $km;
        } catch (\Throwable $e) {
            Log::warning('[RouteDistance] OSRM no disponible, se usa Haversine', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function isValidCoordinate(float $lat, float $lng): bool
    {
        return is_finite($lat) && is_finite($lng)
            && $lat >= -90 && $lat <= 90
            && $lng >= -180 && $lng <= 180
            && !($lat == 0.0 && $lng == 0.0);
    }
}
