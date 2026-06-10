<?php

namespace App\DTO;

use App\Models\PriceHistory;

readonly class PriceHistoryDTO
{
    public function __construct(
        public float $price,
        public string $priceFormatted,
        public string $date,
        public string $dateFormatted,
    ) {}

    /**
     * Converte o DTO para array associativo para a resposta JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'price'           => $this->price,
            'price_formatted' => $this->priceFormatted,
            'date'            => $this->date,
            'date_formatted'  => $this->dateFormatted,
        ];
    }

    /**
     * Instancia o DTO a partir do model Eloquent.
     */
    public static function fromModel(PriceHistory $model): self
    {
        return new self(
            price:          (float) $model->price,
            priceFormatted: 'R$ ' . number_format($model->price, 2, ',', '.'),
            date:           $model->created_at->toIso8601String(),
            dateFormatted:  $model->created_at->format('d/m/Y H:i'),
        );
    }
}
