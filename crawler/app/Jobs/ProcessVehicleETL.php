<?php

namespace App\Jobs;

use App\Services\ETL\Contracts\VehicleETLInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job para processamento ETL de veículos (Transform & Load).
 */
class ProcessVehicleETL implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $rawData;

    /**
     * @param array<string, string> $rawData
     */
    public function __construct(array $rawData)
    {
        $this->rawData = $rawData;
    }

    /**
     * Executa as etapas de Transform e Load usando o VehicleETLInterface.
     */
    public function handle(VehicleETLInterface $etlService): void
    {
        $etlService->execute($this->rawData);
    }
}

