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
}

