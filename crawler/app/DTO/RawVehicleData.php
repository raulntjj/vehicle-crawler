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
        public ?array $images = null,
        public ?int $doors = null,
        public ?string $bodystyle = null,
        public ?string $fuel = null,
        public ?string $transmission = null,
    ) {}

    /**
     * Converte o DTO para array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'external_id'  => $this->externalId,
            'source'       => $this->source,
            'brand'        => $this->brand,
            'model'        => $this->model,
            'title'        => $this->title,
            'price'        => $this->price,
            'km'           => $this->km,
            'year'         => $this->year,
            'url'          => $this->url,
            'images'       => $this->images,
            'doors'        => $this->doors,
            'bodystyle'    => $this->bodystyle,
            'fuel'         => $this->fuel,
            'transmission' => $this->transmission,
        ];
    }

    /**
     * Cria uma instância a partir de um array.
     *
     * @param  array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            externalId:   $data['external_id'],
            source:       $data['source'] ?? 'unknown',
            brand:        $data['brand'] ?? '',
            model:        $data['model'] ?? '',
            title:        $data['title'],
            price:        $data['price'],
            km:           $data['km'],
            year:         $data['year'],
            url:          $data['url'],
            images:       $data['images'] ?? null,
            doors:        isset($data['doors']) ? (int) $data['doors'] : null,
            bodystyle:    $data['bodystyle'] ?? null,
            fuel:         $data['fuel'] ?? null,
            transmission: $data['transmission'] ?? null,
        );
    }
}

