<?php

namespace App\Services\ETL;

use App\Services\ETL\Contracts\VehicleETLInterface;
use App\Services\ETL\Contracts\VehicleRepositoryInterface;
use App\Services\ETL\Contracts\VehicleTransformerInterface;
use Illuminate\Support\Facades\Log;

class VehicleETLService implements VehicleETLInterface
{
    public function __construct(
        protected VehicleTransformerInterface $transformer,
        protected VehicleRepositoryInterface $repository
    ) {}

    /**
     * Executa o fluxo completo de ETL para um veículo.
     *
     * @param array<string, string> $rawData
     */
    public function execute(array $rawData): void
    {
        $externalId = $rawData['external_id'];
        $source     = $rawData['source'] ?? 'unknown';
        $logContext = "[{$source}::{$externalId}]";

        Log::info("[ETL] Processando: {$logContext}");

        $transformedData = $this->transformer->transform($rawData);

        $this->repository->save($transformedData);

        Log::info("[ETL] ✅ Processado com sucesso: {$logContext}");
    }
}
