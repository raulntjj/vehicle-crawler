<?php

namespace App\Repositories\Contracts;

use App\Models\Vehicle;

interface VehicleRepositoryInterface
{
    /**
     * Salva ou atualiza os dados do veículo e registra o histórico de preços se necessário.
     *
     * @param array<string, mixed> $transformedData
     * @return Vehicle
     */
    public function save(array $transformedData): Vehicle;
}
