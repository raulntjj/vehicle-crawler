<?php

namespace App\Services\Crawlers\Contracts;

use App\DTO\RawVehicleData;

/**
 * Contrato para implementações de crawler de veículos.
 */
interface VehicleCrawlerInterface
{
    /**
     * Extrai os dados do portal e retorna uma lista de DTOs.
     *
     * @param  string $keyword
     * @return RawVehicleData[]
     */
    public function crawl(string $keyword): array;

    /**
     * Retorna o identificador do portal de origem.
     *
     * @return string
     */
    public function getSource(): string;
}
