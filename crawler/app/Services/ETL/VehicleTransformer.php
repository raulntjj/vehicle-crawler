<?php

namespace App\Services\ETL;

use App\Services\ETL\Contracts\VehicleTransformerInterface;

class VehicleTransformer implements VehicleTransformerInterface
{
    /**
     * Limpa e transforma os dados brutos de um veículo.
     *
     * @param array<string, string> $rawData
     * @return array<string, mixed>
     */
    public function transform(array $rawData): array
    {
        $externalId = $rawData['external_id'];
        $source     = $rawData['source'] ?? 'unknown';

        $cleanPrice = $this->parsePrice($rawData['price']);
        $cleanKm    = $this->parseKm($rawData['km']);
        [$yearFab, $yearModel] = $this->parseYear($rawData['year']);

        return [
            'external_id'      => $externalId,
            'source'           => $source,
            'brand'            => trim($rawData['brand'] ?? ''),
            'model'            => trim($rawData['model'] ?? ''),
            'title'            => $this->cleanTitle($rawData['title']),
            'price'            => $cleanPrice,
            'km'               => $cleanKm,
            'year_fabrication' => $yearFab,
            'year_model'       => $yearModel,
            'url'              => trim($rawData['url']),
            'images'           => isset($rawData['images']) ? $rawData['images'] : null,
            'doors'            => isset($rawData['doors']) ? (int) $rawData['doors'] : null,
            'bodystyle'        => isset($rawData['bodystyle']) ? trim($rawData['bodystyle']) : null,
            'fuel'             => isset($rawData['fuel']) ? trim($rawData['fuel']) : null,
            'transmission'     => isset($rawData['transmission']) ? trim($rawData['transmission']) : null,
        ];
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
