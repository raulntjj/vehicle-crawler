<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: PriceHistory (Histórico de Preços)
 *
 * Registro imutável de cada variação de preço detectada para um veículo.
 * Não possui `updated_at` pois, por definição de domínio, um registro
 * de histórico nunca é alterado após a criação.
 *
 * @property int    $id
 * @property int    $vehicle_id
 * @property float  $price
 * @property string $created_at
 */
class PriceHistory extends Model
{
    /**
     * Desabilita o updated_at — registros de histórico são imutáveis.
     */
    const UPDATED_AT = null;

    /**
     * Campos permitidos para atribuição em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vehicle_id',
        'price',
    ];

    /**
     * Casting de atributos para tipos nativos do PHP.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price'      => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    /**
     * Veículo ao qual este registro de preço pertence.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
