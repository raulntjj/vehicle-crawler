<?php

namespace App\DTO;

/**
 * Contrato de dados de veículo brutos para a fila ETL.
 */
readonly class RawVehicleData
{
    public function __construct(
        public string $externalId,
        public string $source,
        public string $brand,
        public string $model,
        public string $title,
        public string $price,
        public string $km,
        public string $year,
        public string $url,
    ) {}

    /**
     * Converte o DTO para array.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'source'      => $this->source,
            'brand'       => $this->brand,
            'model'       => $this->model,
            'title'       => $this->title,
            'price'       => $this->price,
            'km'          => $this->km,
            'year'        => $this->year,
            'url'         => $this->url,
        ];
    }

    /**
     * Cria uma instância a partir de um array.
     *
     * @param  array<string, string> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            externalId: $data['external_id'],
            source:     $data['source'] ?? 'unknown',
            brand:      $data['brand'] ?? '',
            model:      $data['model'] ?? '',
            title:      $data['title'],
            price:      $data['price'],
            km:         $data['km'],
            year:       $data['year'],
            url:        $data['url'],
        );
    }
}

