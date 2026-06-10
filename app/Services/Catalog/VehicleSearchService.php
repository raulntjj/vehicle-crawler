<?php

namespace App\Services\Catalog;

use App\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class VehicleSearchService
{
    /**
     * Busca e filtra veículos com paginação.
     *
     * @param  array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $query = Vehicle::query();

        // 1. Termo Geral de Busca (Title, Model, Brand) - Case Insensitive
        if (!empty($filters['search'])) {
            $searchTerm = '%' . strtolower(trim($filters['search'])) . '%';
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->whereRaw('LOWER(title) LIKE ?', [$searchTerm])
                  ->orWhereRaw('LOWER(model) LIKE ?', [$searchTerm])
                  ->orWhereRaw('LOWER(brand) LIKE ?', [$searchTerm]);
            });
        }

        // 2. Filtro de Marcas - Case Insensitive
        if (!empty($filters['brands'])) {
            $brands = array_map('strtolower', $filters['brands']);
            $placeholders = implode(',', array_fill(0, count($brands), '?'));
            $query->whereRaw("LOWER(brand) IN ({$placeholders})", $brands);
        }

        // 3. Filtro de Modelo - Case Insensitive
        if (!empty($filters['model'])) {
            $modelTerm = '%' . strtolower(trim($filters['model'])) . '%';
            $query->whereRaw('LOWER(model) LIKE ?', [$modelTerm]);
        }

        // 4. Filtro de Portais de Origem - Case Insensitive
        if (!empty($filters['sources'])) {
            $sources = array_map('strtolower', $filters['sources']);
            $placeholders = implode(',', array_fill(0, count($sources), '?'));
            $query->whereRaw("LOWER(source) IN ({$placeholders})", $sources);
        }

        // 5. Filtro de Preço
        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }
        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // 6. Filtro de Quilometragem
        if (isset($filters['min_km'])) {
            $query->where('km', '>=', $filters['min_km']);
        }
        if (isset($filters['max_km'])) {
            $query->where('km', '<=', $filters['max_km']);
        }

        // 7. Filtro de Ano
        if (isset($filters['min_year'])) {
            $query->where('year_fabrication', '>=', $filters['min_year']);
        }
        if (isset($filters['max_year'])) {
            $query->where('year_model', '<=', $filters['max_year']);
        }

        // 8. Ordenação
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_direction'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);

        // 9. Paginação
        $perPage = $filters['per_page'] ?? 15;
        return $query->paginate($perPage);
    }
}
