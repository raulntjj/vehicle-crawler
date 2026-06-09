<?php

namespace App\Console\Commands;

use App\Services\Crawlers\CrawlerManager;
use App\Jobs\ProcessVehicleETL;
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
                            {portal : Identificador do portal (ex: "mobiauto")}
                            {keyword=Honda : Termo de busca}';

    /**
     * @var string
     */
    protected $description = 'Extrai anúncios de veículos de um portal e os envia para a fila';

    /**
     * Executa o comando.
     */
    public function handle(CrawlerManager $manager): int
    {
        $portal  = (string) $this->argument('portal');
        $keyword = (string) $this->argument('keyword');

        $this->info("🔍 Buscando no portal [{$portal}] por: \"{$keyword}\"...");
        $this->newLine();

        try {
            $crawler = $manager->driver($portal);
        } catch (InvalidArgumentException $e) {
            $this->error("❌ Erro: " . $e->getMessage());
            return Command::FAILURE;
        }

        $vehicles = $crawler->crawl($keyword);

        if (empty($vehicles)) {
            $this->warn("⚠️  Nenhum veículo encontrado.");
            return Command::SUCCESS;
        }

        $this->info("📦 " . count($vehicles) . " veículo(s) encontrado(s). Despachando para a fila...");
        $this->newLine();

        foreach ($vehicles as $vehicle) {
            ProcessVehicleETL::dispatch($vehicle->toArray())->onQueue('etl-vehicles');

            $this->line("  ✅ [{$vehicle->source}::{$vehicle->externalId}] {$vehicle->title}");
            $this->line("     Preço: {$vehicle->price} | KM: {$vehicle->km} | Ano: {$vehicle->year}");
            $this->newLine();
        }

        $this->info('🚀 Processo de extração concluído.');
        return Command::SUCCESS;
    }
}
