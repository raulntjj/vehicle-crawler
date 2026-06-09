<?php

namespace App\Console\Commands;

use App\Services\Crawlers\CrawlerManager;
use App\Services\Crawlers\Contracts\VehicleCrawlerInterface;
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
                            {keyword? : Termo de busca opcional (se vazio, busca todas as marcas)}';

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
        $keyword = $this->argument('keyword') ? (string) $this->argument('keyword') : null;

        try {
            $crawler = $manager->driver($portal);
        } catch (InvalidArgumentException $e) {
            $this->error("❌ Erro: " . $e->getMessage());
            return Command::FAILURE;
        }

        if ($keyword !== null) {
            $this->info("🔍 Buscando no portal [{$portal}] por: \"{$keyword}\"...");
            $this->newLine();
            $this->crawlAndDispatch($crawler, $keyword);
        } else {
            $brands = config('crawler.brands', []);

            if (empty($brands)) {
                $this->error("❌ Nenhuma marca configurada em config/crawler.php");
                return Command::FAILURE;
            }

            $this->info("🔍 Buscando todas as (" . count($brands) . ") marcas configuradas no portal [{$portal}]...");
            $this->newLine();

            $delay = (int) config('crawler.delay_between_brands', 2);

            foreach ($brands as $index => $brand) {
                $this->info("👉 Extraindo marca: [{$brand}] (" . ($index + 1) . "/" . count($brands) . ")...");
                $this->crawlAndDispatch($crawler, $brand);

                if ($index < count($brands) - 1 && $delay > 0) {
                    $this->line("⏱️  Aguardando {$delay} segundos antes da próxima marca...");
                    $this->newLine();
                    sleep($delay);
                }
            }
        }

        $this->info('🚀 Processo de extração concluído.');
        return Command::SUCCESS;
    }

    /**
     * Executa a busca de veículos para uma palavra-chave e despacha os Jobs.
     */
    private function crawlAndDispatch(VehicleCrawlerInterface $crawler, string $keyword): void
    {
        $vehicles = $crawler->crawl($keyword);

        if (empty($vehicles)) {
            $this->warn("⚠️  Nenhum veículo encontrado para \"{$keyword}\".");
            $this->newLine();
            return;
        }

        $this->info("📦 " . count($vehicles) . " veículo(s) encontrado(s). Despachando para a fila...");
        $this->newLine();

        foreach ($vehicles as $vehicle) {
            ProcessVehicleETL::dispatch($vehicle->toArray())->onQueue('etl-vehicles');

            $this->line("  ✅ [{$vehicle->source}::{$vehicle->externalId}] {$vehicle->title}");
            $this->line("     Preço: {$vehicle->price} | KM: {$vehicle->km} | Ano: {$vehicle->year}");
            $this->newLine();
        }
    }
}

