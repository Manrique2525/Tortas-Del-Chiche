<?php

namespace Tests\Unit;

use App\Services\RouteDistanceService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RouteDistanceServiceTest extends TestCase
{
    private RouteDistanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RouteDistanceService();
    }

    public function test_uses_route_distance_when_osrm_responds(): void
    {
        Http::fake([
            'router.project-osrm.org/*' => Http::response([
                'code' => 'Ok',
                'routes' => [['distance' => 4300.0, 'duration' => 720.0]],
            ], 200),
        ]);

        $result = $this->service->getDistance(17.9866, -92.9531, 17.9950, -92.9400);

        $this->assertSame('route', $result['source']);
        $this->assertSame(4.3, $result['km']);
    }

    public function test_converts_meters_to_kilometers(): void
    {
        Http::fake([
            'router.project-osrm.org/*' => Http::response([
                'routes' => [['distance' => 12345.0]],
            ], 200),
        ]);

        $this->assertSame(12.35, $this->service->getRouteDistance(17.9866, -92.9531, 17.9950, -92.9400));
    }

    public function test_falls_back_to_haversine_when_osrm_returns_error(): void
    {
        Http::fake([
            'router.project-osrm.org/*' => Http::response('Server Error', 500),
        ]);

        $result = $this->service->getDistance(17.9866, -92.9531, 17.9950, -92.9400);

        $this->assertSame('haversine', $result['source']);
        $this->assertGreaterThan(0.0, $result['km']);
    }

    public function test_falls_back_to_haversine_when_osrm_throws(): void
    {
        Http::fake(function () {
            throw new ConnectionException('connection timed out');
        });

        $result = $this->service->getDistance(17.9866, -92.9531, 17.9950, -92.9400);

        $this->assertSame('haversine', $result['source']);
        $this->assertGreaterThan(0.0, $result['km']);
    }

    public function test_falls_back_when_osrm_payload_is_invalid(): void
    {
        Http::fake([
            'router.project-osrm.org/*' => Http::response(['code' => 'NoRoute'], 200),
        ]);

        $result = $this->service->getDistance(17.9866, -92.9531, 17.9950, -92.9400);

        $this->assertSame('haversine', $result['source']);
    }

    public function test_rejects_invalid_coordinates_without_calling_api(): void
    {
        Http::fake();

        $this->assertNull($this->service->getRouteDistance(0.0, 0.0, 17.9, -92.9));
        $this->assertNull($this->service->getRouteDistance(95.0, -92.9, 17.9, -92.9));

        Http::assertNothingSent();
    }

    public function test_haversine_distance_is_geographically_reasonable(): void
    {
        $km = $this->service->haversineDistance(17.0, -92.0, 18.0, -92.0);

        $this->assertGreaterThan(110.0, $km);
        $this->assertLessThan(112.0, $km);
    }
}
