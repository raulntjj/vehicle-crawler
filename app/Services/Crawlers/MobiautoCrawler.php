<?php

namespace App\Services\Crawlers;

use App\DTO\RawVehicleData;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Driver para o portal Mobiauto.
 */
class MobiautoCrawler implements VehicleCrawlerInterface
{
    private const SOURCE = 'mobiauto';
    private const API_ENDPOINT = 'https://api.mobiauto.com.br/search/api/vehicle/v1.0/open-search';

    private const HEADERS = [
        'User-Agent'      => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0',
        'Accept'          => 'application/json, text/plain, */*',
        'Accept-Language' => 'en-US,en;q=0.9',
        'Origin'          => 'https://www.mobiauto.com.br',
        'Sec-Fetch-Site'  => 'same-site',
        'Sec-Fetch-Mode'  => 'cors',
        'Sec-Fetch-Dest'  => 'empty',
    ];

    /**
     * @return RawVehicleData[]
     */
    public function crawl(string $keyword): array
    {
        try {
            $rawModels = $this->fetchRawModels($keyword);
        } catch (ConnectionException $e) {
            Log::error('[MobiautoCrawler] Falha de conexão', [
                'keyword' => $keyword,
                'error'   => $e->getMessage(),
            ]);

            return [];
        }

        return array_values(array_filter(
            array_map(fn (array $model) => $this->normalize($model), $rawModels)
        ));
    }

    public function getSource(): string
    {
        return self::SOURCE;
    }

    /**
     * @return array<int, array<string, mixed>>
     * @throws ConnectionException
     */
    private function fetchRawModels(string $keyword): array
    {
        $response = Http::withHeaders(self::HEADERS)
            ->timeout(15)
            ->get(self::API_ENDPOINT, [
                'keyword'     => $keyword,
                'vehicleType' => 'CAR',
                'isServer'    => 'true',
            ]);

        if ($response->failed()) {
            Log::warning('[MobiautoCrawler] Falha HTTP', [
                'keyword' => $keyword,
                'status'  => $response->status(),
            ]);

            return [];
        }

        return $response->json('models') ?? [];
    }

    private function normalize(array $model): ?RawVehicleData
    {
        $id = (string) ($model['id'] ?? '');

        if ($id === '') {
            return null;
        }

        $make      = (string) ($model['makeName'] ?? $model['brandName'] ?? '');
        $name      = (string) ($model['name'] ?? $model['modelName'] ?? 'Desconhecido');
        $modelYear = isset($model['modelYear']) ? (int) $model['modelYear'] : null;

        [$yearFab, $yearModel, $rawPrice, $rawKm] = $this->buildSimulatedFields($id, $modelYear);

        return new RawVehicleData(
            externalId: $id,
            source:     self::SOURCE,
            title:      trim("{$make} {$name}"),
            price:      $rawPrice,
            km:         $rawKm,
            year:       "{$yearFab}/{$yearModel}",
            url:        $this->buildCanonicalUrl($make, $name, $id),
        );
    }

    /**
     * Gera campos simulados com seed determinístico baseado no ID do modelo.
     *
     * @return array{0: int, 1: int, 2: string, 3: string}
     */
    private function buildSimulatedFields(string $id, ?int $modelYear): array
    {
        $seed = abs(crc32($id));

        $price     = 45000 + ($seed % 155000);
        $km        = 5000  + ($seed % 95000);
        $yearModel = $modelYear ?? (2019 + ($seed % 6));
        $yearFab   = $yearModel - ($seed % 2);

        return [
            $yearFab,
            $yearModel,
            'R$ ' . number_format($price, 2, ',', '.'),
            number_format($km, 0, ',', '.') . ' km',
        ];
    }

    private function buildCanonicalUrl(string $make, string $name, string $id): string
    {
        $slug = str(strtolower("{$make}-{$name}"))->slug()->value();

        return "https://www.mobiauto.com.br/carros/{$slug}/id-{$id}";
    }
}
