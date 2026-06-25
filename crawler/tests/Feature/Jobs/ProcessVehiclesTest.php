<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessVehicles;
use App\Models\PriceHistory;
use App\Models\Vehicle;
use App\Repositories\Contracts\RawVehicleRepositoryInterface;
use App\Repositories\Contracts\VehicleRepositoryInterface;
use App\Services\ETL\Contracts\VehicleTransformerInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessVehiclesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reads_from_mongo_transforms_and_saves(): void
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

        $fakeMongoId = '665f1a2b3c4d5e6f7a8b9c0d';

        // Mock do RawVehicleRepository para retornar o documento bruto e atualizar status
        $mockRawRepo = $this->mock(RawVehicleRepositoryInterface::class);
        $mockRawRepo->shouldReceive('findById')
            ->with($fakeMongoId)
            ->once()
            ->andReturn($rawData);

        $mockRawRepo->shouldReceive('updateStatus')
            ->with($fakeMongoId, 'processed')
            ->once();

        $transformer = $this->app->make(VehicleTransformerInterface::class);
        $repository = $this->app->make(VehicleRepositoryInterface::class);

        $job = new ProcessVehicles($fakeMongoId, '99999');
        $job->handle($mockRawRepo, $transformer, $repository);

        $this->assertDatabaseHas('vehicles', [
            'external_id' => '99999',
            'brand'       => 'Toyota',
            'model'       => 'Corolla',
            'price'       => 130000.00,
        ]);

        $vehicle = Vehicle::where('external_id', '99999')->first();
        $this->assertNotNull($vehicle);
        $this->assertDatabaseHas('price_histories', [
            'vehicle_id' => $vehicle->id,
            'price'      => 130000.00,
        ]);
    }

    public function test_it_handles_missing_mongo_document_gracefully(): void
    {
        $fakeMongoId = '665f1a2b3c4d5e6f7a8b9c0d';

        $mockRawRepo = $this->mock(RawVehicleRepositoryInterface::class);
        $mockRawRepo->shouldReceive('findById')
            ->with($fakeMongoId)
            ->once()
            ->andReturn(null);

        $transformer = $this->app->make(VehicleTransformerInterface::class);
        $repository = $this->app->make(VehicleRepositoryInterface::class);

        $job = new ProcessVehicles($fakeMongoId, '99999');
        $job->handle($mockRawRepo, $transformer, $repository);

        $this->assertDatabaseMissing('vehicles', [
            'external_id' => '99999',
        ]);
    }

    public function test_it_marks_status_as_failed_on_job_failure(): void
    {
        $fakeMongoId = '665f1a2b3c4d5e6f7a8b9c0d';

        $mockRawRepo = $this->mock(RawVehicleRepositoryInterface::class);
        $mockRawRepo->shouldReceive('updateStatus')
            ->with($fakeMongoId, 'failed')
            ->once();

        $job = new ProcessVehicles($fakeMongoId, '99999');
        $job->failed(new \Exception('Test failure'));
    }
}
