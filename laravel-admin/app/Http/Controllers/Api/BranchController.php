<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;

class BranchController extends Controller
{
    public function index(): JsonResponse
    {
        $branches = Branch::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($branch) {
                return [
                    'key'           => $branch->key,
                    'name'          => $branch->name,
                    'address'       => $branch->address,
                    'phone'         => $branch->phone,
                    'whatsapp'      => $branch->whatsapp,
                    'schedule'      => $branch->schedule,
                    'schedule_text' => $branch->schedule_text,
                    'latitude'      => (float) $branch->latitude,
                    'longitude'     => (float) $branch->longitude,
                    'didi_url'      => $branch->didi_url,
                    'is_open'       => $branch->is_open,
                ];
            });

        return response()->json($branches);
    }
}
