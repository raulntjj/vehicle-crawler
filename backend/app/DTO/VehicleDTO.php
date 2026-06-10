<?php

namespace App\DTO;

use App\Models\Vehicle;

readonly class VehicleDTO
{
    /**
     * @param  PriceHistoryDTO[]|null $priceHistory
     */
    public function __construct(
        public int $id,
        public string $externalId,
        public string $brand,
        public string $model,
        public string $title,
        public float $price,
        public string $priceFormatted,
        public int $km,
        public string $kmFormatted,
        public int $yearFabrication,
        public int $yearModel,
        public string $yearFormatted,
        public string $url,
        public string $source,
        public ?array $priceHistory,
        public string $createdAt,
        public string $updatedAt,
        public ?array $images = null,
        public ?int $doors = null,
        public ?string $bodystyle = null,
        public ?string $fuel = null,
        public ?string $transmission = null,
    ) {}

    /**
     * Converte o DTO para array associativo para a resposta JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'external_id'      => $this->externalId,
            'brand'            => $this->brand,
            'model'            => $this->model,
            'title'            => $this->title,
            'price'            => $this->price,
            'price_formatted'  => $this->priceFormatted,
            'km'               => $this->km,
            'km_formatted'     => $this->kmFormatted,
            'year_fabrication' => $this->yearFabrication,
            'year_model'       => $this->yearModel,
            'year_formatted'   => $this->yearFormatted,
            'url'              => $this->url,
            'source'           => $this->source,
            'price_history'    => $this->priceHistory !== null
                ? array_map(fn(PriceHistoryDTO $dto) => $dto->toArray(), $this->priceHistory)
                : null,
            'created_at'       => $this->createdAt,
            'updated_at'       => $this->updatedAt,
            'images'           => $this->images,
            'doors'            => $this->doors,
            'bodystyle'        => $this->bodystyle,
            'fuel'             => $this->fuel,
            'transmission'     => $this->transmission,
        ];
    }

    /**
     * Instancia o DTO a partir do model Eloquent.
     */
    public static function fromModel(Vehicle $model, bool $includeHistory = false): self
    {
        $priceHistory = null;
        if ($includeHistory && $model->relationLoaded('priceHistories')) {
            $priceHistory = $model->priceHistories
                ->map(fn($history) => PriceHistoryDTO::fromModel($history))
                ->all();
        }

        return new self(
            id:              $model->id,
            externalId:      $model->external_id,
            brand:           $model->brand,
            model:           $model->model,
            title:           $model->title,
            price:           (float) $model->price,
            priceFormatted:  'R$ ' . number_format($model->price, 2, ',', '.'),
            km:              $model->km,
            kmFormatted:     number_format($model->km, 0, ',', '.') . ' km',
            yearFabrication: $model->year_fabrication,
            yearModel:       $model->year_model,
            yearFormatted:   $model->year_fabrication === $model->year_model
                ? (string) $model->year_fabrication
                : "{$model->year_fabrication}/{$model->year_model}",
            url:             $model->url,
            source:          $model->source,
            priceHistory:    $priceHistory,
            createdAt:       $model->created_at->toIso8601String(),
            updatedAt:       $model->updated_at->toIso8601String(),
            images:          $model->images,
            doors:           $model->doors,
            bodystyle:       $model->bodystyle,
            fuel:            $model->fuel,
            transmission:    $model->transmission,
        );
    }
}
