<?php

namespace App\Jobs;

use App\Models\PriceHistory;
use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para processamento ETL de veículos (Transform & Load).
 */
class ProcessVehicleETL implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    private array $rawData;

    /**
     * @param array<string, string> $rawData
     */
    public function __construct(array $rawData)
    {
        $this->rawData = $rawData;
    }

    /**
     * Executa as etapas de Transform e Load.
     */
    public function handle(): void
    {
        $externalId = $this->rawData['external_id'];
        $source     = $this->rawData['source'] ?? 'unknown';
        $logContext = "[{$source}::{$externalId}]";

        Log::info("[ETL] Processando: {$logContext}");

        $cleanPrice = $this->parsePrice($this->rawData['price']);
        $cleanKm    = $this->parseKm($this->rawData['km']);
        [$yearFab, $yearModel] = $this->parseYear($this->rawData['year']);

        $transformedData = [
            'source'           => $source,
            'title'            => $this->cleanTitle($this->rawData['title']),
            'price'            => $cleanPrice,
            'km'               => $cleanKm,
            'year_fabrication' => $yearFab,
            'year_model'       => $yearModel,
            'url'              => trim($this->rawData['url']),
        ];

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

        Log::info("[ETL] ✅ Processado com sucesso: {$logContext}");
    }

    private function cleanTitle(string $rawTitle): string
    {
        return preg_replace('/\s+/', ' ', trim($rawTitle));
    }

    private function parsePrice(string $rawPrice): float
    {
        $cleaned = preg_replace('/R\$\s*/', '', $rawPrice);
        $cleaned = trim($cleaned);
        $cleaned = str_replace('.', '', $cleaned);
        $cleaned = str_replace(',', '.', $cleaned);

        return (float) $cleaned;
    }

    private function parseKm(string $rawKm): int
    {
        $cleaned = preg_replace('/\s*km\s*/i', '', $rawKm);
        $cleaned = str_replace('.', '', $cleaned);

        return (int) trim($cleaned);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function parseYear(string $rawYear): array
    {
        $parts = explode('/', trim($rawYear));
        $yearFabrication = (int) trim($parts[0] ?? 0);
        $yearModel       = (int) trim($parts[1] ?? $yearFabrication);

        return [$yearFabrication, $yearModel];
    }
}
