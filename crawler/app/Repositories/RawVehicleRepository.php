<?php

namespace App\Repositories;

use App\Repositories\Contracts\RawVehicleRepositoryInterface;
use Illuminate\Support\Facades\DB;

class RawVehicleRepository implements RawVehicleRepositoryInterface
{
    /**
     * Nome da collection no MongoDB para armazenar veículos brutos.
     */
    private const COLLECTION = 'raw_vehicles';

    /**
     * Armazena o JSON bruto de um veículo na Staging Area (MongoDB).
     *
     * @param array<string, mixed> $rawData
     * @return string O _id do documento inserido no MongoDB em formato string.
     */
    public function store(array $rawData): string
    {
        return (string) DB::connection('mongodb')
            ->table(self::COLLECTION)
            ->insertGetId($rawData);
    }

    /**
     * Recupera o documento bruto pelo seu _id do MongoDB.
     *
     * @param string $mongoId
     * @return array<string, mixed>|null
     */
    public function findById(string $mongoId): ?array
    {
        $document = DB::connection('mongodb')
            ->table(self::COLLECTION)
            ->where('_id', $mongoId)
            ->first();

        if ($document === null) {
            return null;
        }

        return (array) $document;
    }

    /**
     * Busca o documento mais recente de um veículo pelo external_id e portal de origem (source).
     */
    public function findByExternalIdAndSource(string $externalId, string $source): ?array
    {
        $document = DB::connection('mongodb')
            ->table(self::COLLECTION)
            ->where('external_id', $externalId)
            ->where('source', $source)
            ->orderBy('_id', 'desc')
            ->first();

        if ($document === null) {
            return null;
        }

        return (array) $document;
    }

    /**
     * Atualiza os dados brutos e status de um documento específico no MongoDB.
     */
    public function update(string $mongoId, array $rawData): void
    {
        DB::connection('mongodb')
            ->table(self::COLLECTION)
            ->where('_id', $mongoId)
            ->update($rawData);
    }

    /**
     * Atualiza apenas o status de processamento de um documento no MongoDB.
     */
    public function updateStatus(string $mongoId, string $status): void
    {
        DB::connection('mongodb')
            ->table(self::COLLECTION)
            ->where('_id', $mongoId)
            ->update(['status' => $status]);
    }
}

