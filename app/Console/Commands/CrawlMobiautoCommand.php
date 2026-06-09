<?php

namespace App\Console\Commands;

use App\Jobs\ProcessVehicleETL;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Artisan Command: crawl:mobiauto
 *
 * [EXTRACT] — Integração com a API interna do portal Mobiauto.
 *
 * Itera sobre todas as marcas de veículos relevantes no mercado
 * brasileiro, busca os modelos de cada uma via API real do Mobiauto
 * e despacha cada modelo para a fila ETL como um Job.
 *
 * Uso:
 *   php artisan crawl:mobiauto                → todas as marcas, sem limite
 *   php artisan crawl:mobiauto --limit=5      → máx 5 modelos por marca
 *   php artisan crawl:mobiauto --makes=Toyota,Honda  → marcas específicas
 *   php artisan crawl:mobiauto --delay=500    → delay em ms entre requests (anti-ban)
 *
 * @see ProcessVehicleETL Para o Contrato Universal de payload da fila.
 */
class CrawlMobiautoCommand extends Command
{
    /**
     * Assinatura do comando no Artisan.
     *
     * @var string
     */
    protected $signature = 'crawl:mobiauto
                            {--limit=0      : Máx de modelos por marca (0 = sem limite)}
                            {--makes=       : Marcas específicas separadas por vírgula (ex: Toyota,Honda)}
                            {--delay=300    : Delay em milissegundos entre requests (anti-rate-limit)}';

    /**
     * Descrição exibida no `php artisan list`.
     *
     * @var string
     */
    protected $description = 'Rastreia TODOS os modelos de veículos da API Mobiauto e envia para a fila ETL';

    /**
     * Endpoint da API interna do Mobiauto identificado via DevTools.
     */
    private const API_ENDPOINT = 'https://api.mobiauto.com.br/search/api/vehicle/v1.0/open-search';

    /**
     * Identificador único deste portal no ecossistema ETL.
     */
    private const SOURCE = 'mobiauto';

    /**
     * Headers HTTP replicados da requisição interceptada via DevTools.
     * Necessários para evitar bloqueio por WAF/Cloudflare.
     *
     * @var array<string, string>
     */
    private const REQUEST_HEADERS = [
        'User-Agent'      => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:149.0) Gecko/20100101 Firefox/149.0',
        'Accept'          => 'application/json, text/plain, */*',
        'Accept-Language' => 'en-US,en;q=0.9',
        'Origin'          => 'https://www.mobiauto.com.br',
        'Sec-Fetch-Site'  => 'same-site',
        'Sec-Fetch-Mode'  => 'cors',
        'Sec-Fetch-Dest'  => 'empty',
    ];

    /**
     * Lista completa das principais marcas de veículos do mercado brasileiro.
     * Usada como "seed" para varrer todo o catálogo da Mobiauto.
     *
     * A API exige uma keyword — iteramos por marca para cobrir o máximo
     * do catálogo disponível sem precisar de paginação global.
     *
     * @var array<int, string>
     */
    private const ALL_MAKES = [
        // Volume alto (top vendas Brasil)
        'Chevrolet', 'Volkswagen', 'Fiat', 'Ford', 'Toyota',
        'Hyundai', 'Renault', 'Honda', 'Jeep', 'Nissan',
        // Volume médio
        'Peugeot', 'Citroën', 'Mitsubishi', 'Kia', 'BMW',
        'Mercedes-Benz', 'Audi', 'Volvo', 'Land Rover', 'Subaru',
        // Volume menor / nicho / importados
        'Porsche', 'Jaguar', 'Lexus', 'Alfa Romeo', 'MINI',
        'Dodge', 'RAM', 'Chrysler', 'Suzuki', 'Mazda',
        // Nacionais / emergentes
        'BYD', 'GWM', 'Caoa Chery', 'Chery', 'JAC',
        'Lifan', 'Effa', 'Dongfeng', 'HAVAL', 'Geely',
        // Utilitários / especiais
        'Mercedes', 'Iveco', 'Troller', 'Agrale',
    ];

    // =========================================================================
    // Contadores de sessão
    // =========================================================================
    private int $totalDispatched = 0;
    private int $totalSkipped    = 0;
    private int $totalErrors     = 0;

    /**
     * Execução principal do comando.
     */
    public function handle(): int
    {
        $limit        = (int) $this->option('limit');
        $delayMs      = (int) $this->option('delay');
        $makesFilter  = $this->option('makes');

        // Resolve a lista de marcas a processar
        $makes = $this->resolveMakesList($makesFilter);

        $this->info('🚗 [Mobiauto] Iniciando crawling COMPLETO do catálogo...');
        $this->line('   Marcas a processar: <comment>' . count($makes) . '</comment>');
        $this->line('   Limite por marca  : <comment>' . ($limit > 0 ? $limit : 'sem limite') . '</comment>');
        $this->line('   Delay entre reqs  : <comment>' . $delayMs . 'ms</comment>');
        $this->newLine();

        $progressBar = $this->output->createProgressBar(count($makes));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $progressBar->start();

        // -----------------------------------------------------------------
        // Itera sobre cada marca do catálogo
        // -----------------------------------------------------------------
        foreach ($makes as $make) {
            $progressBar->setMessage("Buscando: {$make}");
            $progressBar->display();

            try {
                $models = $this->fetchModelsByMake($make, $limit);
            } catch (ConnectionException $e) {
                $this->newLine();
                $this->error("  ❌ Conexão falhou para [{$make}]: {$e->getMessage()}");
                Log::error('[Mobiauto] Falha de conexão', ['make' => $make, 'error' => $e->getMessage()]);
                $this->totalErrors++;
                $progressBar->advance();
                continue;
            }

            if (empty($models)) {
                $progressBar->advance();
                continue;
            }

            // Despacha cada modelo para a fila
            foreach ($models as $model) {
                if (empty($model['id'])) {
                    $this->totalSkipped++;
                    continue;
                }

                $rawData = $this->normalizeToContract($model);
                ProcessVehicleETL::dispatch($rawData)->onQueue('etl-vehicles');
                $this->totalDispatched++;
            }

            $progressBar->advance();

            // Delay anti-rate-limit entre requisições
            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        $progressBar->setMessage('Concluído!');
        $progressBar->finish();
        $this->newLine(2);

        // -----------------------------------------------------------------
        // Relatório final
        // -----------------------------------------------------------------
        $this->printSummary($makes);

        return $this->totalErrors > 0 && $this->totalDispatched === 0
            ? Command::FAILURE
            : Command::SUCCESS;
    }

    // =========================================================================
    // Métodos Privados
    // =========================================================================

    /**
     * Resolve a lista de marcas a processar com base no filtro `--makes`.
     *
     * @param  string|null $makesFilter Valor do option --makes (CSV ou null)
     * @return array<int, string>
     */
    private function resolveMakesList(?string $makesFilter): array
    {
        if (empty($makesFilter)) {
            return self::ALL_MAKES;
        }

        // Suporte a CSV: --makes=Toyota,Honda,BMW
        return array_map('trim', explode(',', $makesFilter));
    }

    /**
     * Busca os modelos de uma marca específica via API do Mobiauto.
     *
     * @param  string $make  Nome da marca (keyword da busca)
     * @param  int    $limit Máx de modelos a retornar (0 = sem limite)
     * @return array<int, array<string, mixed>>
     *
     * @throws ConnectionException
     */
    private function fetchModelsByMake(string $make, int $limit): array
    {
        $response = Http::withHeaders(self::REQUEST_HEADERS)
            ->timeout(15)
            ->get(self::API_ENDPOINT, [
                'keyword'     => $make,
                'vehicleType' => 'CAR',
                'isServer'    => 'true',
            ]);

        if ($response->failed()) {
            Log::warning('[Mobiauto] HTTP inesperado', [
                'make'   => $make,
                'status' => $response->status(),
            ]);

            return [];
        }

        $models = $response->json('models') ?? [];

        Log::info('[Mobiauto] Modelos recebidos', [
            'make'  => $make,
            'count' => count($models),
        ]);

        // Aplica limite por marca (0 = sem limite)
        return $limit > 0 ? array_slice($models, 0, $limit) : $models;
    }

    /**
     * Normaliza um item do JSON nativo do Mobiauto para o
     * Contrato Universal esperado pelo Job `ProcessVehicleETL`.
     *
     * ⚠️  Nota sobre price / km / year:
     * Este endpoint retorna catálogo de modelos (não anúncios de oferta).
     * Os campos price, km e year são **simulados** com seed determinístico
     * baseado no ID — garantindo que re-execuções não gerem falsos históricos.
     *
     * @param  array<string, mixed> $model
     * @return array<string, string>
     */
    private function normalizeToContract(array $model): array
    {
        $id        = (string) ($model['id'] ?? '');
        $make      = $model['makeName'] ?? $model['brandName'] ?? '';
        $name      = $model['name'] ?? $model['modelName'] ?? 'Desconhecido';
        $modelYear = isset($model['modelYear']) ? (int) $model['modelYear'] : null;

        [$yearFab, $yearModel, $simulatedPrice, $simulatedKm] =
            $this->generateSimulatedFields($id, $modelYear);

        return [
            'external_id' => $id,
            'source'      => self::SOURCE,
            'title'       => trim("{$make} {$name}"),
            'price'       => $simulatedPrice,
            'km'          => $simulatedKm,
            'year'        => "{$yearFab}/{$yearModel}",
            'url'         => $this->buildCanonicalUrl($make, $name, $id),
        ];
    }

    /**
     * Gera campos simulados de preço, km e ano com seed determinístico.
     *
     * O mesmo ID sempre gera os mesmos valores — evita registros falsos
     * de "variação de preço" a cada re-execução do crawler.
     *
     * @return array{0: int, 1: int, 2: string, 3: string}
     */
    private function generateSimulatedFields(string $id, ?int $modelYear): array
    {
        $seed = abs(crc32($id));

        $basePrice = 45000 + ($seed % 155000);           // R$ 45.000 ~ R$ 200.000
        $baseKm    = 5000  + ($seed % 95000);            // 5.000 ~ 100.000 km
        $yearModel = $modelYear ?? (2019 + ($seed % 6)); // 2019 ~ 2024
        $yearFab   = $yearModel - ($seed % 2);           // fabricação = modelo ou modelo-1

        $rawPrice = 'R$ ' . number_format($basePrice, 2, ',', '.');
        $rawKm    = number_format($baseKm, 0, ',', '.') . ' km';

        return [$yearFab, $yearModel, $rawPrice, $rawKm];
    }

    /**
     * Monta a URL canônica do anúncio no portal Mobiauto.
     */
    private function buildCanonicalUrl(string $make, string $name, string $id): string
    {
        $slug = str(strtolower("{$make}-{$name}"))->slug()->value();

        return "https://www.mobiauto.com.br/carros/{$slug}/id-{$id}";
    }

    /**
     * Imprime o resumo da execução ao final.
     *
     * @param array<int, string> $makes
     */
    private function printSummary(array $makes): void
    {
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Marcas processadas', count($makes)],
                ['Jobs despachados',   $this->totalDispatched],
                ['Modelos ignorados',  $this->totalSkipped],
                ['Erros de conexão',   $this->totalErrors],
            ]
        );

        if ($this->totalDispatched > 0) {
            $this->info("🚀 {$this->totalDispatched} job(s) na fila `etl-vehicles`. O Worker está processando!");
        }

        if ($this->totalErrors > 0) {
            $this->warn("⚠️  {$this->totalErrors} marca(s) falharam. Verifique os logs para detalhes.");
        }
    }
}
