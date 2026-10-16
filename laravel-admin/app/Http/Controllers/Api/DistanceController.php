<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\RouteDistanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DistanceController extends Controller
{
    public function show(Request $request, RouteDistanceService $routeDistance): JsonResponse
    {
        $data = $request->validate([
            'branch' => 'required|string',
            'lat'    => 'required|numeric|between:-90,90',
            'lng'    => 'required|numeric|between:-180,180',
        ]);

        $branch = Branch::where('key', $data['branch'])
            ->where('is_active', true)
            ->first();

        if (!$branch || !$branch->latitude || !$branch->longitude) {
            return response()->json([
                'success' => false,
                'message' => 'Sucursal no encontrada',
            ], 404);
        }

        $result = $routeDistance->getDistance(
            (float) $branch->latitude,
            (float) $branch->longitude,
            (float) $data['lat'],
            (float) $data['lng'],
        );

        return response()->json([
            'success'         => true,
            'distance_km'     => $result['km'],
            'distance_meters' => (int) round($result['km'] * 1000),
            'source'          => $result['source'],
        ]);
    }
}
