<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Services\OrderTotalCalculator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeliveryFeeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
    }

    private function createSchema(): void
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

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 8, 2);
        });

        Schema::create('branch_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('product_id');
            $table->boolean('active')->default(true);
            $table->json('available_options')->nullable();
            $table->decimal('price_override', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    private function createBranch(): void
    {
        Branch::create([
            'key'       => 'atasta',
            'name'      => 'Sucursal Atasta',
            'schedule'  => ['days' => ['monday'], 'open' => '07:00', 'close' => '14:00'],
            'latitude'  => 17.9866,
            'longitude' => -92.9531,
            'is_active' => true,
        ]);
    }

    public function test_delivery_fee_uses_route_distance(): void
    {
        $this->createBranch();

        Http::fake([
            'router.project-osrm.org/*' => Http::response([
                'routes' => [['distance' => 4300.0]],
            ], 200),
        ]);

        $result = (new OrderTotalCalculator())->calculate(
            [],
            0,
            null,
            'atasta',
            'domicilio',
            17.9950,
            -92.9400,
        );

        // 4.3 km -> ceil = 5 -> 1 km extra -> 40 + 10 = 50
        $this->assertSame(50.0, $result['delivery_fee']);
    }

    public function test_delivery_fee_falls_back_to_haversine_when_api_fails(): void
    {
        $this->createBranch();

        Http::fake([
            'router.project-osrm.org/*' => Http::response('', 500),
        ]);

        $result = (new OrderTotalCalculator())->calculate(
            [],
            0,
            null,
            'atasta',
            'domicilio',
            17.9870,
            -92.9530,
        );

        // Very close point -> within base 4 km -> 40
        $this->assertSame(40.0, $result['delivery_fee']);
    }

    public function test_pickup_has_no_delivery_fee(): void
    {
        $this->createBranch();
        Http::fake();

        $result = (new OrderTotalCalculator())->calculate(
            [],
            0,
            null,
            'atasta',
            'recoger',
            17.9950,
            -92.9400,
        );

        $this->assertEquals(0, $result['delivery_fee']);
        Http::assertNothingSent();
    }

    public function test_delivery_fee_is_capped_at_maximum(): void
    {
        $this->createBranch();

        Http::fake([
            'router.project-osrm.org/*' => Http::response([
                'routes' => [['distance' => 60000.0]],
            ], 200),
        ]);

        $result = (new OrderTotalCalculator())->calculate(
            [],
            0,
            null,
            'atasta',
            'domicilio',
            18.5000,
            -93.5000,
        );

        $this->assertSame(120.0, $result['delivery_fee']);
    }
}
