<?php

namespace App\Console\Commands;

use App\Jobs\ProcessVehicleETL;
use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Artisan Command: crawl:vehicles
 *
 * [EXTRACT] — Primeira etapa do pipeline ETL.
 *
 * Simula o rastreamento de anúncios de veículos seminovos,
 * parseando um HTML fictício com dados intencionalmente "sujos"
 * e despachando cada anúncio para a fila RabbitMQ como um Job.
 *
 * Em produção, o HTML seria obtido via HTTP (Guzzle/Http Facade)
 * a partir de sites reais de classificados.
 *
 * Uso: php artisan crawl:vehicles
 */
class CrawlVehiclesCommand extends Command
{
    /**
     * Assinatura do comando no Artisan.
     *
     * @var string
     */
    protected $signature = 'crawl:vehicles';

    /**
     * Descrição exibida no `php artisan list`.
     *
     * @var string
     */
    protected $description = 'Rastreia anúncios de veículos seminovos e envia os dados brutos para a fila ETL';

    /**
     * Execução principal do comando.
     */
    public function handle(): int
    {
        $this->info('🔍 Iniciando crawling de veículos...');
        $this->newLine();

        // -----------------------------------------------------------------
        // HTML fictício simulando uma página de listagem de veículos.
        // Os dados estão intencionalmente "sujos" para demonstrar
        // a etapa de Transform no Job.
        // -----------------------------------------------------------------
        $html = $this->getFakeHtml();

        // -----------------------------------------------------------------
        // Utiliza o Symfony DomCrawler para parsear o HTML
        // -----------------------------------------------------------------
        $crawler = new Crawler($html);

        $ads = $crawler->filter('.vehicle-card');

        if ($ads->count() === 0) {
            $this->warn('⚠️  Nenhum anúncio encontrado no HTML.');
            return Command::SUCCESS;
        }

        $this->info("📦 {$ads->count()} anúncio(s) encontrado(s). Despachando para a fila...");
        $this->newLine();

        // -----------------------------------------------------------------
        // Para cada anúncio, extrai os dados brutos e despacha para a fila
        // -----------------------------------------------------------------
        $ads->each(function (Crawler $node, int $index) {
            $rawData = [
                'external_id' => trim($node->attr('data-id')),
                'title'       => trim($node->filter('.title')->text()),
                'price'       => trim($node->filter('.price')->text()),
                'km'          => trim($node->filter('.km')->text()),
                'year'        => trim($node->filter('.year')->text()),
                'url'         => trim($node->filter('.link a')->attr('href')),
            ];

            // Despacha o Job para a fila `etl-vehicles` no RabbitMQ
            ProcessVehicleETL::dispatch($rawData)->onQueue('etl-vehicles');

            $this->line("  ✅ [{$rawData['external_id']}] {$rawData['title']}");
            $this->line("     Preço: {$rawData['price']} | KM: {$rawData['km']} | Ano: {$rawData['year']}");
            $this->newLine();
        });

        $this->info('🚀 Todos os anúncios foram enviados para a fila `etl-vehicles`!');
        $this->info('   O Worker processará os Jobs automaticamente.');

        return Command::SUCCESS;
    }

    /**
     * Retorna HTML fictício simulando uma página de classificados.
     *
     * Os dados estão propositalmente em formatos "sujos" (com R$, pontos,
     * vírgulas, sufixo "km", ano no formato fabricação/modelo) para
     * exercitar a lógica de Transform no Job.
     */
    private function getFakeHtml(): string
    {
        return <<<'HTML'
        <html>
        <body>
            <div class="vehicle-listing">

                <!-- Anúncio 1: Honda City Hatch -->
                <div class="vehicle-card" data-id="HC-2023-001">
                    <h2 class="title">Honda City Hatch EXL 1.5 Flex Aut.</h2>
                    <span class="price">R$ 95.900,00</span>
                    <span class="km">24.500 km</span>
                    <span class="year">2022/2023</span>
                    <div class="link">
                        <a href="https://www.exemplo-classificados.com.br/anuncio/honda-city-hatch-exl-HC-2023-001">
                            Ver anúncio
                        </a>
                    </div>
                </div>

                <!-- Anúncio 2: Toyota Corolla Cross -->
                <div class="vehicle-card" data-id="TCC-2024-042">
                    <h2 class="title">Toyota Corolla Cross XRE 2.0 Flex</h2>
                    <span class="price">R$ 179.990,00</span>
                    <span class="km">8.320 km</span>
                    <span class="year">2023/2024</span>
                    <div class="link">
                        <a href="https://www.exemplo-classificados.com.br/anuncio/toyota-corolla-cross-xre-TCC-2024-042">
                            Ver anúncio
                        </a>
                    </div>
                </div>

                <!-- Anúncio 3: Volkswagen Polo -->
                <div class="vehicle-card" data-id="VW-POLO-2022-118">
                    <h2 class="title">  Volkswagen Polo Highline 200 TSI   </h2>
                    <span class="price"> R$   89.500,00 </span>
                    <span class="km"> 45.230 km </span>
                    <span class="year"> 2021/2022 </span>
                    <div class="link">
                        <a href="https://www.exemplo-classificados.com.br/anuncio/vw-polo-highline-VW-POLO-2022-118">
                            Ver anúncio
                        </a>
                    </div>
                </div>

                <!-- Anúncio 4: Hyundai HB20 (dados bem sujos para testar edge cases) -->
                <div class="vehicle-card" data-id="HB20-2023-200">
                    <h2 class="title">Hyundai HB20 Platinum Plus 1.0 Turbo</h2>
                    <span class="price">R$98.750,50</span>
                    <span class="km">12.100km</span>
                    <span class="year">2023/2023</span>
                    <div class="link">
                        <a href="https://www.exemplo-classificados.com.br/anuncio/hyundai-hb20-platinum-HB20-2023-200">
                            Ver anúncio
                        </a>
                    </div>
                </div>

                <!-- Anúncio 5: Chevrolet Tracker -->
                <div class="vehicle-card" data-id="CHEV-TRACK-055">
                    <h2 class="title">Chevrolet Tracker Premier 1.2 Turbo AT</h2>
                    <span class="price">R$ 142.000,00</span>
                    <span class="km">31.800 km</span>
                    <span class="year">2022/2023</span>
                    <div class="link">
                        <a href="https://www.exemplo-classificados.com.br/anuncio/chevrolet-tracker-premier-CHEV-TRACK-055">
                            Ver anúncio
                        </a>
                    </div>
                </div>

            </div>
        </body>
        </html>
        HTML;
    }
}
