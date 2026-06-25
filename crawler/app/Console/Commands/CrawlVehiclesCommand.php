<?php

namespace App\Console\Commands;

use App\Jobs\CrawlVehicles;
use App\Repositories\Contracts\BrandRepositoryInterface;
use App\Services\Crawlers\CrawlerManager;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Ponto de entrada para a extração de anúncios.
 */
class CrawlVehiclesCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'crawl:vehicles
                            {portal? : Identificador do portal opcional (ex: "mobiauto")}
                            {keyword? : Termo de busca opcional (se vazio, busca todas as marcas)}';

    /**
     * @var string
     */
    protected $description = 'Extrai anúncios de veículos de portais e os envia para a fila';

    /**
     * Executa o comando.
     */
    public function handle(CrawlerManager $manager, BrandRepositoryInterface $brandRepository): int
    {
        $portalInput = $this->argument('portal') ? (string) $this->argument('portal') : null;
        $keyword = $this->argument('keyword') ? (string) $this->argument('keyword') : null;

        $availablePortals = $manager->getAvailableDrivers();

        if ($portalInput !== null) {
            try {
                // Valida se o portal é suportado
                $manager->driver($portalInput);
                $portals = [strtolower(trim($portalInput))];
            } catch (InvalidArgumentException $e) {
                $this->error("❌ Erro: " . $e->getMessage());
                return Command::FAILURE;
            }
        } else {
            $portals = $availablePortals;
        }

        if (empty($portals)) {
            $this->error("❌ Nenhum portal disponível para extração.");
            return Command::FAILURE;
        }

        $this->info("🔍 Agendando extração para os portais: " . implode(', ', $portals) . "...");
        $this->newLine();

        foreach ($portals as $portal) {
            CrawlVehicles::dispatchForPortal($portal, $keyword, $brandRepository);
            $this->line("👉 Portal [{$portal}] despachado para a fila (portals.crawl)");
        }

        $this->newLine();
        $this->info('🚀 Todos os portais foram agendados com sucesso.');
        return Command::SUCCESS;
    }
}
