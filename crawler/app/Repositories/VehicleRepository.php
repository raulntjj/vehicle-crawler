<?php

namespace App\Repositories;

use App\Models\PriceHistory;
use App\Models\Vehicle;
use App\Repositories\Contracts\VehicleRepositoryInterface;
use Illuminate\Support\Facades\Log;

class VehicleRepository implements VehicleRepositoryInterface
{
    /**
     * Salva ou atualiza os dados do veículo e registra o histórico de preços se necessário.
     *
     * @param array<string, mixed> $transformedData
     * @return Vehicle
     */
    public function save(array $transformedData): Vehicle
    {
        $externalId = $transformedData['external_id'];
        $source     = $transformedData['source'];
        $cleanPrice = $transformedData['price'];
        $logContext = "[{$source}::{$externalId}]";

        $uniqueKey = ['external_id' => $externalId, 'source' => $source];
        $existingVehicle = Vehicle::where($uniqueKey)->first();

        $vehicle = Vehicle::updateOrCreate($uniqueKey, $transformedData);

        $isNew = $existingVehicle === null;
        $priceChanged = !$isNew && (float) $existingVehicle->price !== $cleanPrice;

        if ($isNew || $priceChanged) {
            PriceHistory::create([
                'vehicle_id' => $vehicle->id,
                'price'      => $cleanPrice,
            ]);

            $action = $isNew ? 'NOVO' : 'PREÇO ALTERADO';
            Log::info("[ETL] [{$action}] Histórico registrado para {$logContext}: R$ {$cleanPrice}");
        }

        return $vehicle;
    }
}
