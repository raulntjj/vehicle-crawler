<?php

namespace Tests\Feature\Repositories;

use App\Models\PriceHistory;
use App\Models\Vehicle;
use App\Repositories\VehicleRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private VehicleRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new VehicleRepository();
    }

    public function test_it_saves_new_vehicle_and_records_initial_price_history(): void
    {
        $transformedData = [
            'external_id'      => '77777',
            'source'           => 'mobiauto',
            'brand'            => 'Chevrolet',
            'model'            => 'Onix',
            'title'            => 'Chevrolet Onix',
            'price'            => 85000.00,
            'km'               => 10000,
            'year_fabrication' => 2021,
            'year_model'       => 2022,
            'url'              => 'https://example.com/onix',
        ];

        $vehicle = $this->repository->save($transformedData);

        $this->assertInstanceOf(Vehicle::class, $vehicle);
        $this->assertDatabaseHas('vehicles', [
            'id'          => $vehicle->id,
            'external_id' => '77777',
            'brand'       => 'Chevrolet',
            'model'       => 'Onix',
            'price'       => 85000.00,
        ]);

        $this->assertDatabaseHas('price_histories', [
            'vehicle_id' => $vehicle->id,
            'price'      => 85000.00,
        ]);
    }

    public function test_it_updates_vehicle_and_creates_price_history_on_price_change(): void
    {
        $transformedData = [
            'external_id'      => '77777',
            'source'           => 'mobiauto',
            'brand'            => 'Chevrolet',
            'model'            => 'Onix',
            'title'            => 'Chevrolet Onix',
            'price'            => 85000.00,
            'km'               => 10000,
            'year_fabrication' => 2021,
            'year_model'       => 2022,
            'url'              => 'https://example.com/onix',
        ];

        $vehicle1 = $this->repository->save($transformedData);

        // Update price
        $transformedData['price'] = 82000.00;
        $vehicle2 = $this->repository->save($transformedData);

        $this->assertEquals($vehicle1->id, $vehicle2->id);
        $this->assertDatabaseHas('vehicles', [
            'id'    => $vehicle1->id,
            'price' => 82000.00,
        ]);

        $this->assertEquals(2, PriceHistory::where('vehicle_id', $vehicle1->id)->count());
    }
}
