<?php

namespace App\Repositories;

use App\Models\Brand;
use App\Repositories\Contracts\BrandRepositoryInterface;

class BrandRepository implements BrandRepositoryInterface
{
    /**
     * Retorna os nomes de todas as marcas ativas.
     *
     * @return array<int, string>
     */
    public function getActiveBrandNames(): array
    {
        return Brand::where('is_active', true)
            ->pluck('name')
            ->toArray();
    }
}
