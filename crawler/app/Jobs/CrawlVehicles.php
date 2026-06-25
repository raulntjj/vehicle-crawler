<?php

namespace App\Jobs;

use App\Repositories\Contracts\BrandRepositoryInterface;
use App\Repositories\Contracts\RawVehicleRepositoryInterface;
use App\Services\Crawlers\CrawlerManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job unificado de Extração (o "E" do ETL).
 *
 * Responsável por:
 *  1. Resolver as localidades e marcas a partir do portal.
 *  2. Executar o Scraping via driver do portal.
 *  3. Despejar cada JSON bruto no MongoDB (Staging Area).
 *  4. Despachar um ProcessVehicles por documento para o pipeline T+L.
 */
class CrawlVehicles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param string      $portal   Identificador do portal (ex: 'mobiauto').
     * @param string      $location Localidade no formato slug (ex: 'sp-sao-paulo').
     * @param string      $brand    Nome da marca a ser rastreada.
     */
    public function __construct(
        public string $portal,
        public string $location,
        public string $brand
    ) {}

    /**
     * Execute o job.
     */
    public function handle(
        CrawlerManager $manager,
        RawVehicleRepositoryInterface $rawVehicleRepository
    ): void {
        Log::info("[CrawlVehicles] Iniciando extração", [
            'portal'   => $this->portal,
            'location' => $this->location,
            'brand'    => $this->brand,
        ]);

        try {
            $crawler = $manager->driver($this->portal);
            $vehicles = $crawler->crawl($this->brand, $this->location);

            if (empty($vehicles)) {
                Log::info("[CrawlVehicles] Nenhum veículo encontrado", [
                    'portal'   => $this->portal,
                    'location' => $this->location,
                    'brand'    => $this->brand,
                ]);
                return;
            }

            Log::info("[CrawlVehicles] Encontrado(s) " . count($vehicles) . " veículo(s). Despejando no MongoDB e despachando processamento", [
                'portal'   => $this->portal,
                'location' => $this->location,
                'brand'    => $this->brand,
            ]);

            foreach ($vehicles as $vehicle) {
                $rawData = $vehicle->toArray();
                $externalId = $rawData['external_id'] ?? 'unknown';
                $source = $rawData['source'] ?? 'unknown';

                // Verifica se já temos registro deste veículo no MongoDB
                $existing = $rawVehicleRepository->findByExternalIdAndSource($externalId, $source);
                $hash = md5(json_encode($rawData));

                if ($existing !== null) {
                    $existingHash = $existing['hash'] ?? '';
                    $existingStatus = $existing['status'] ?? 'pending';

                    // Se os dados não mudaram e o status for 'processed' ou 'pending', ignoramos o processamento
                    if ($existingHash === $hash && in_array($existingStatus, ['processed', 'pending'], true)) {
                        Log::info("[CrawlVehicles] Veículo ignorado (sem alterações ou processamento em fila)", [
                            'external_id' => $externalId,
                            'status'      => $existingStatus,
                        ]);
                        continue;
                    }

                    // Se mudou ou falhou, atualizamos os dados e marcamos como 'pending'
                    $rawData['hash'] = $hash;
                    $rawData['status'] = 'pending';
                    $mongoId = (string) ($existing['id'] ?? $existing['_id'] ?? '');
                    $rawVehicleRepository->update($mongoId, $rawData);

                    Log::info("[CrawlVehicles] Veículo atualizado no MongoDB para reprocessamento", [
                        'external_id' => $externalId,
                        'mongo_id'    => $mongoId,
                        'reason'      => $existingHash !== $hash ? 'dados_alterados' : 'retry_falha',
                    ]);
                } else {
                    // Novo veículo
                    $rawData['hash'] = $hash;
                    $rawData['status'] = 'pending';
                    $mongoId = $rawVehicleRepository->store($rawData);

                    Log::info("[CrawlVehicles] Novo veículo cadastrado no MongoDB", [
                        'external_id' => $externalId,
                        'mongo_id'    => $mongoId,
                    ]);
                }

                // Dispara o job de Transform & Load com a referência ao documento no Mongo
                ProcessVehicles::dispatch($mongoId, $externalId)->onQueue('vehicles.process');
            }
        } catch (\Throwable $e) {
            Log::error("[CrawlVehicles] Erro na execução do crawler", [
                'portal'   => $this->portal,
                'location' => $this->location,
                'brand'    => $this->brand,
                'error'    => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Despacha jobs de CrawlVehicles para todas as combinações de localidade × marca
     * de um determinado portal. Método estático de conveniência usado pelo Command.
     *
     * @param string      $portal  Identificador do portal.
     * @param string|null $keyword Marca específica (se null, busca todas as ativas).
     */
    public static function dispatchForPortal(
        string $portal,
        ?string $keyword,
        BrandRepositoryInterface $brandRepository
    ): void {
        $locations = config('crawler.default_locations', ['sp-sao-paulo', 'mg-belo-horizonte']);
        $delay = (int) config('crawler.delay_between_brands', 2);

        foreach ($locations as $location) {
            if ($keyword !== null) {
                // Keyword explícita — despacha apenas essa marca
                static::dispatch($portal, $location, $keyword)
                    ->onQueue('portals.crawl');
            } else {
                // Busca todas as marcas ativas via repositório
                $brands = $brandRepository->getActiveBrandNames();

                if (empty($brands)) {
                    Log::warning("[CrawlVehicles] Nenhuma marca ativa configurada no banco de dados.");
                    return;
                }

                foreach ($brands as $index => $brand) {
                    $job = static::dispatch($portal, $location, $brand)
                        ->onQueue('portals.crawl');

                    $delaySeconds = $index * $delay;
                    if ($delaySeconds > 0) {
                        $job->delay(now()->addSeconds($delaySeconds));
                    }
                }
            }
        }
    }
}
