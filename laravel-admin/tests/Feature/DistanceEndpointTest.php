<?php

namespace Tests\Feature;

use App\Models\Branch;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DistanceEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSucursalesTable();
    }

    private function createSucursalesTable(): void
    {
        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('whatsapp', 50)->nullable();
            $table->json('schedule');
            $table->string('schedule_text')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('didi_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    private function createBranch(): Branch
    {
        return Branch::create([
            'key'       => 'atasta',
            'name'      => 'Sucursal Atasta',
            'schedule'  => ['days' => ['monday'], 'open' => '07:00', 'close' => '14:00'],
            'latitude'  => 17.9866,
            'longitude' => -92.9531,
            'is_active' => true,
        ]);
    }

    public function test_returns_route_distance_when_api_available(): void
    {
        $this->createBranch();

        Http::fake([
            'router.project-osrm.org/*' => Http::response([
                'routes' => [['distance' => 4300.0]],
            ], 200),
        ]);

        $this->getJson('/api/distance?branch=atasta&lat=17.9950&lng=-92.9400')
            ->assertOk()
            ->assertJson([
                'success'         => true,
                'distance_km'     => 4.3,
                'distance_meters' => 4300,
                'source'          => 'route',
            ]);
    }

    public function test_returns_haversine_fallback_when_api_fails(): void
    {
        $this->createBranch();

        Http::fake([
            'router.project-osrm.org/*' => Http::response('', 503),
        ]);

        $this->getJson('/api/distance?branch=atasta&lat=17.9950&lng=-92.9400')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'source'  => 'haversine',
            ])
            ->assertJsonPath('distance_km', fn ($km) => $km > 0);
    }

    public function test_validates_required_parameters(): void
    {
        $this->getJson('/api/distance')->assertStatus(422);
        $this->getJson('/api/distance?branch=atasta')->assertStatus(422);
        $this->getJson('/api/distance?branch=atasta&lat=999&lng=-92.9')->assertStatus(422);
    }

    public function test_returns_404_for_unknown_or_inactive_branch(): void
    {
        Http::fake();

        $this->getJson('/api/distance?branch=nope&lat=17.9&lng=-92.9')->assertStatus(404);

        $this->createBranch();
        Branch::where('key', 'atasta')->update(['is_active' => false]);

        $this->getJson('/api/distance?branch=atasta&lat=17.9&lng=-92.9')->assertStatus(404);
    }
}
