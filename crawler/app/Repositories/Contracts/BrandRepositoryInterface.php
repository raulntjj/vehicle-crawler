<?php

namespace App\Repositories\Contracts;

interface BrandRepositoryInterface
{
    /**
     * Retorna os nomes de todas as marcas ativas.
     *
     * @return array<int, string>
     */
    public function getActiveBrandNames(): array;
}
