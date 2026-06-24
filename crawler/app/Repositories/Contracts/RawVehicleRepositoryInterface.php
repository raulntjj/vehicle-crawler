<?php

namespace App\Repositories\Contracts;

interface RawVehicleRepositoryInterface
{
    /**
     * Armazena o JSON bruto de um veículo na Staging Area (MongoDB).
     *
     * @param array<string, mixed> $rawData
     * @return string O _id do documento inserido no MongoDB em formato string.
     */
    public function store(array $rawData): string;

    /**
     * Recupera o documento bruto pelo seu _id do MongoDB.
     *
     * @param string $mongoId
     * @return array<string, mixed>|null
     */
    public function findById(string $mongoId): ?array;
}
