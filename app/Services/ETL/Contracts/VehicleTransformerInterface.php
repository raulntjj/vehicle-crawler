<?php

namespace App\Services\ETL\Contracts;

interface VehicleTransformerInterface
{
    /**
     * Limpa e transforma os dados brutos de um veículo.
     *
     * @param array<string, string> $rawData
     * @return array<string, mixed>
     */
    public function transform(array $rawData): array;
}
