<?php

namespace App\Services\ETL\Contracts;

interface VehicleETLInterface
{
    /**
     * Executa o fluxo completo de ETL para um veículo.
     *
     * @param array<string, string> $rawData
     */
    public function execute(array $rawData): void;
}
