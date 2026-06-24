<?php

namespace App\Jobs;

use App\Repositories\Contracts\RawVehicleRepositoryInterface;
use App\Repositories\Contracts\VehicleRepositoryInterface;
use App\Services\ETL\Contracts\VehicleTransformerInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job de processamento de veículos (Transform & Load).
 *
 * Lê o JSON bruto imutável armazenado no MongoDB (Staging Area)
 * através do RawVehicleRepository, aplica a transformação via
 * VehicleTransformer e persiste na base de dados relacional
 * final via VehicleRepository.
 */
class ProcessVehicles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param string $mongoId    Identificador único do documento no MongoDB (_id em formato string).
     * @param string $externalId ID externo do veículo no portal de origem.
     */
    public function __construct(
        public string $mongoId,
        public string $externalId
    ) {}

    /**
     * Executa as etapas de Transform e Load.
     *
     * 1. Recupera o JSON bruto do MongoDB pelo mongo_id via RawVehicleRepository.
     * 2. Injeta no VehicleTransformer para mapear os dados limpos.
     * 3. Persiste via VehicleRepository.
     */
    public function handle(
        RawVehicleRepositoryInterface $rawVehicleRepository,
        VehicleTransformerInterface $transformer,
        VehicleRepositoryInterface $repository
    ): void {
        $rawData = $rawVehicleRepository->findById($this->mongoId);

        if ($rawData === null) {
            Log::error("[ProcessVehicles] Documento não encontrado no MongoDB", [
                'mongo_id'    => $this->mongoId,
                'external_id' => $this->externalId,
            ]);
            return;
        }

        Log::info("[ProcessVehicles] Processando veículo", [
            'mongo_id'    => $this->mongoId,
            'external_id' => $this->externalId,
        ]);

        $transformedData = $transformer->transform($rawData);
        $repository->save($transformedData);

        Log::info("[ProcessVehicles] ✅ Veículo processado com sucesso", [
            'mongo_id'    => $this->mongoId,
            'external_id' => $this->externalId,
        ]);
    }
}
