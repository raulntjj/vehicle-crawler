<?php

namespace App\Services\Crawlers\Drivers;

use App\DTO\RawVehicleData;
use App\Services\Crawlers\Contracts\VehicleCrawlerInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Driver para o portal Mobiauto.
 */
class MobiautoCrawler implements VehicleCrawlerInterface
{
    private const SOURCE = 'mobiauto';
    private const BASE_URL = 'https://www.mobiauto.com.br/comprar/carros';

    private const HEADERS = [
        'User-Agent'      => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',
        'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Accept-Language' => 'en-US,en;q=0.5',
    ];

    /**
     * @return RawVehicleData[]
     */
    public function crawl(string $keyword): array
    {
        try {
            $rawDeals = $this->fetchRawDeals($keyword);
        } catch (ConnectionException $e) {
            Log::error('[MobiautoCrawler] Falha de conexão', [
                'keyword' => $keyword,
                'error'   => $e->getMessage(),
            ]);

            return [];
        }

        return array_values(array_filter(
            array_map(fn (array $deal) => $this->normalize($deal), $rawDeals)
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
    private function fetchRawDeals(string $keyword): array
    {
        $location = config('crawler.default_location', 'sp-sao-paulo');
        $slugBrand = str(strtolower($keyword))->slug()->value();
        
        $url = self::BASE_URL . "/{$location}/{$slugBrand}";

        $response = Http::withHeaders(self::HEADERS)
            ->timeout(15)
            ->get($url);

        if ($response->failed()) {
            Log::warning('[MobiautoCrawler] Falha HTTP', [
                'keyword' => $keyword,
                'status'  => $response->status(),
            ]);

            return [];
        }

        $html = $response->body();

        if (!preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/', $html, $matches)) {
            Log::warning('[MobiautoCrawler] __NEXT_DATA__ não encontrado na página', [
                'keyword' => $keyword,
                'url'     => $url,
            ]);
            return [];
        }

        $json = json_decode($matches[1], true);
        
        return $json['props']['pageProps']['deals']['results'] ?? [];
    }

    private function normalize(array $deal): ?RawVehicleData
    {
        $id = (string) ($deal['id'] ?? '');

        if ($id === '') {
            return null;
        }

        $make = (string) ($deal['trim']['make']['name'] ?? '');
        $modelName = (string) ($deal['trim']['model']['name'] ?? '');
        $versionName = (string) ($deal['trim']['name'] ?? '');
        
        // Ex: "Honda City DX 1.5 (Flex)"
        $title = trim("{$make} {$modelName} {$versionName}");

        $price = isset($deal['price']) ? (float) $deal['price'] : null;
        $rawPrice = $price !== null ? 'R$ ' . number_format($price, 2, ',', '.') : '';

        $km = isset($deal['km']) ? (int) $deal['km'] : null;
        $rawKm = $km !== null ? number_format($km, 0, ',', '.') . ' km' : '';

        $yearFab = $deal['trim']['productionYear'] ?? null;
        $yearModel = $deal['trim']['model']['year'] ?? null;
        $rawYear = '';
        if ($yearFab && $yearModel) {
            $rawYear = "{$yearFab}/{$yearModel}";
        } elseif ($yearFab) {
            $rawYear = (string) $yearFab;
        } elseif ($yearModel) {
            $rawYear = (string) $yearModel;
        }

        $url = $this->buildCanonicalUrl($deal);

        return new RawVehicleData(
            externalId: $id,
            source:     self::SOURCE,
            brand:      $make,
            model:      $modelName,
            title:      $title,
            price:      $rawPrice,
            km:         $rawKm,
            year:       $rawYear,
            url:        $url,
        );
    }

    private function buildCanonicalUrl(array $deal): string
    {
        $id = $deal['id'] ?? '';
        $state = strtolower($deal['dealer']['location']['state'] ?? 'br');
        $city = str(strtolower($deal['dealer']['location']['city'] ?? 'brasil'))->slug()->value();
        $make = str(strtolower($deal['trim']['make']['name'] ?? ''))->slug()->value();
        $model = str(strtolower($deal['trim']['model']['name'] ?? ''))->slug()->value();
        $year = $deal['trim']['model']['year'] ?? $deal['trim']['productionYear'] ?? '0';
        $version = str(strtolower($deal['trim']['name'] ?? ''))->slug()->value();

        return "https://www.mobiauto.com.br/comprar/carros/{$state}-{$city}/{$make}/{$model}/{$year}/{$version}/detalhes/{$id}?page=detail";
    }
}

