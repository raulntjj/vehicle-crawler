<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model: Vehicle (Veículo rastreado)
 *
 * Representa um anúncio de veículo com dados já limpos/transformados.
 * Utiliza `external_id` como chave natural para evitar duplicatas
 * entre diferentes execuções do crawler.
 *
 * @property int    $id
 * @property string $external_id
 * @property string $source        Portal de origem (ex: 'mobiauto', 'webmotors')
 * @property string $title
 * @property float  $price
 * @property int    $km
 * @property int    $year_fabrication
 * @property int    $year_model
 * @property string $url
 */
class Vehicle extends Model
{
    /**
     * Campos permitidos para atribuição em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'external_id',
        'source',
        'title',
        'price',
        'km',
        'year_fabrication',
        'year_model',
        'url',
    ];

    /**
     * Casting de atributos para tipos nativos do PHP.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price'            => 'decimal:2',
            'km'               => 'integer',
            'year_fabrication' => 'integer',
            'year_model'       => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    /**
     * Histórico de preços do veículo.
     * Ordenado do mais recente para o mais antigo.
     */
    public function priceHistories(): HasMany
    {
        return $this->hasMany(PriceHistory::class)->orderByDesc('created_at');
    }
}
