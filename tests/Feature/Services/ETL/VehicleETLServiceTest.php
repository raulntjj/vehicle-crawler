<?php

namespace Tests\Feature\Services\ETL;

use App\Jobs\ProcessVehicleETL;
use App\Models\PriceHistory;
use App\Models\Vehicle;
use App\Services\ETL\Contracts\VehicleETLInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleETLServiceTest extends TestCase
{
    use RefreshDatabase;

    private VehicleETLInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(VehicleETLInterface::class);
    }

    public function test_it_transforms_and_loads_raw_vehicle_data(): void
    {
        $rawData = [
            'external_id' => '12345',
            'source'      => 'mobiauto',
            'brand'       => 'Honda',
            'model'       => 'Civic',
            'title'       => '   Honda   Civic  2.0   ',
            'price'       => 'R$ 120.000,50',
            'km'          => '15.000 km',
            'year'        => '2022/2023',
            'url'         => '  https://example.com/civic  ',
        ];

        $this->service->execute($rawData);

        $this->assertDatabaseHas('vehicles', [
            'external_id'      => '12345',
            'source'           => 'mobiauto',
            'brand'            => 'Honda',
            'model'            => 'Civic',
            'title'            => 'Honda Civic 2.0',
            'price'            => 120000.50,
            'km'               => 15000,
            'year_fabrication' => 2022,
            'year_model'       => 2023,
            'url'              => 'https://example.com/civic',
        ]);

        $vehicle = Vehicle::where('external_id', '12345')->first();
        $this->assertNotNull($vehicle);

        $this->assertDatabaseHas('price_histories', [
            'vehicle_id' => $vehicle->id,
            'price'      => 120000.50,
        ]);
    }

    public function test_it_updates_existing_vehicle_and_registers_price_history_on_price_change(): void
    {
        // First execution (creates the vehicle)
        $rawData1 = [
            'external_id' => '12345',
            'source'      => 'mobiauto',
            'brand'       => 'Honda',
            'model'       => 'Civic',
            'title'       => 'Honda Civic 2.0',
            'price'       => 'R$ 120.000,00',
            'km'          => '15.000 km',
            'year'        => '2022/2023',
            'url'         => 'https://example.com/civic',
        ];

        $this->service->execute($rawData1);
        $vehicle = Vehicle::where('external_id', '12345')->first();
        
        $this->assertEquals(1, PriceHistory::where('vehicle_id', $vehicle->id)->count());

        // Second execution with different price
        $rawData2 = array_merge($rawData1, ['price' => 'R$ 115.000,00']);
        $this->service->execute($rawData2);

        $this->assertDatabaseHas('vehicles', [
            'id'    => $vehicle->id,
            'price' => 115000.00,
        ]);

        // Should have 2 price history records now
        $this->assertEquals(2, PriceHistory::where('vehicle_id', $vehicle->id)->count());
        $this->assertDatabaseHas('price_histories', [
            'vehicle_id' => $vehicle->id,
            'price'      => 115000.00,
        ]);
    }

    public function test_it_updates_existing_vehicle_but_does_not_register_price_history_when_price_is_same(): void
    {
        $rawData = [
            'external_id' => '12345',
            'source'      => 'mobiauto',
            'brand'       => 'Honda',
            'model'       => 'Civic',
            'title'       => 'Honda Civic 2.0',
            'price'       => 'R$ 120.000,00',
            'km'          => '15.000 km',
            'year'        => '2022/2023',
            'url'         => 'https://example.com/civic',
        ];

        $this->service->execute($rawData);
        $vehicle = Vehicle::where('external_id', '12345')->first();

        // Run again with the same price, but a different title
        $rawData2 = array_merge($rawData, ['title' => 'Honda Civic 2.0 Sport']);
        $this->service->execute($rawData2);

        $this->assertDatabaseHas('vehicles', [
            'id'    => $vehicle->id,
            'title' => 'Honda Civic 2.0 Sport',
            'price' => 120000.00,
        ]);

        // Should still have only 1 price history record
        $this->assertEquals(1, PriceHistory::where('vehicle_id', $vehicle->id)->count());
    }

    public function test_job_delegates_to_etl_service(): void
    {
        $rawData = [
            'external_id' => '99999',
            'source'      => 'mobiauto',
            'brand'       => 'Toyota',
            'model'       => 'Corolla',
            'title'       => 'Toyota Corolla',
            'price'       => 'R$ 130.000,00',
            'km'          => '5.000 km',
            'year'        => '2023/2023',
            'url'         => 'https://example.com/corolla',
        ];

        $job = new ProcessVehicleETL($rawData);
        $job->handle($this->service);

        $this->assertDatabaseHas('vehicles', [
            'external_id' => '99999',
            'brand'       => 'Toyota',
            'model'       => 'Corolla',
            'price'       => 130000.00,
        ]);
    }

}
