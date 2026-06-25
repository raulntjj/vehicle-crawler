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

    /**
     * Busca o documento mais recente de um veículo pelo external_id e portal de origem (source).
     *
     * @param string $externalId
     * @param string $source
     * @return array<string, mixed>|null
     */
    public function findByExternalIdAndSource(string $externalId, string $source): ?array;

    /**
     * Atualiza os dados brutos e status de um documento específico no MongoDB.
     *
     * @param string $mongoId
     * @param array<string, mixed> $rawData
     */
    public function update(string $mongoId, array $rawData): void;

    /**
     * Atualiza apenas o status de processamento de um documento no MongoDB.
     *
     * @param string $mongoId
     * @param string $status
     */
    public function updateStatus(string $mongoId, string $status): void;
}
