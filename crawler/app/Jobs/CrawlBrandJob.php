<?php

namespace App\Jobs;

use App\Services\Crawlers\CrawlerManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CrawlBrandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * O número de vezes que o job pode ser tentado.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Crie uma nova instância do job.
     */
    public function __construct(
        public string $portal,
        public string $location,
        public string $brand
    ) {}

    /**
     * Execute o job.
     */
    public function handle(CrawlerManager $manager): void
    {
        Log::info("[CrawlBrandJob] Iniciando crawler no portal", [
            'portal'   => $this->portal,
            'location' => $this->location,
            'brand'    => $this->brand,
        ]);

        try {
            $crawler = $manager->driver($this->portal);
            $vehicles = $crawler->crawl($this->brand, $this->location);

            if (empty($vehicles)) {
                Log::info("[CrawlBrandJob] Nenhum veículo encontrado", [
                    'portal'   => $this->portal,
                    'location' => $this->location,
                    'brand'    => $this->brand,
                ]);
                return;
            }

            Log::info("[CrawlBrandJob] Encontrado(s) " . count($vehicles) . " veículo(s). Despachando para ETL", [
                'portal'   => $this->portal,
                'location' => $this->location,
                'brand'    => $this->brand,
            ]);

            foreach ($vehicles as $vehicle) {
                ProcessVehicleETL::dispatch($vehicle->toArray())->onQueue('etl-vehicles');
            }
        } catch (\Throwable $e) {
            Log::error("[CrawlBrandJob] Erro na execução do crawler", [
                'portal'   => $this->portal,
                'location' => $this->location,
                'brand'    => $this->brand,
                'error'    => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
