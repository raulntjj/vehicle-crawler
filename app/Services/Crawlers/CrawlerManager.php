<?php

namespace App\Services\Crawlers;

use App\Services\Crawlers\Contracts\VehicleCrawlerInterface;
use App\Services\Crawlers\Drivers\MobiautoCrawler;
use InvalidArgumentException;

/**
 * Gerencia a resolução dos drivers de Crawler.
 */
class CrawlerManager
{
    /**
     * Mapeamento de drivers de crawler disponíveis.
     *
     * @var array<string, class-string<VehicleCrawlerInterface>>
     */
    protected array $drivers = [
        'mobiauto' => MobiautoCrawler::class,
    ];

    /**
     * Resolve e retorna a instância do driver de crawler solicitado.
     *
     * @param  string $name
     * @return VehicleCrawlerInterface
     *
     * @throws InvalidArgumentException
     */
    public function driver(string $name): VehicleCrawlerInterface
    {
        $name = strtolower(trim($name));

        if (!isset($this->drivers[$name])) {
            throw new InvalidArgumentException(
                "Driver [{$name}] não suportado. Drivers disponíveis: " . implode(', ', array_keys($this->drivers))
            );
        }

        return app($this->drivers[$name]);
    }

    /**
     * Retorna a lista de drivers (portais) suportados.
     *
     * @return array<string>
     */
    public function getAvailableDrivers(): array
    {
        return array_keys($this->drivers);
    }
}

