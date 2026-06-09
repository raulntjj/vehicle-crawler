<?php

namespace Tests\Feature\Console\Commands;

use App\Jobs\ProcessVehicleETL;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CrawlVehiclesCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_it_crawls_single_brand_when_keyword_is_provided(): void
    {
        $mockJson = json_encode([
            'props' => [
                'pageProps' => [
                    'deals' => [
                        'results' => [
                            [
                                'id' => 11111,
                                'price' => 75990,
                                'km' => 119300,
                                'trim' => [
                                    'name' => 'Civic',
                                    'productionYear' => 2019,
                                    'make' => ['name' => 'Honda'],
                                    'model' => ['name' => 'Civic', 'year' => 2019]
                                ],
                                'dealer' => [
                                    'location' => [
                                        'state' => 'MG',
                                        'city' => 'Caratinga'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $mockHtml = "<html><body><script id=\"__NEXT_DATA__\" type=\"application/json\">{$mockJson}</script></body></html>";

        Http::fake([
            'www.mobiauto.com.br/comprar/carros/*' => Http::response($mockHtml, 200)
        ]);

        $this->artisan('crawl:vehicles mobiauto Honda')
            ->expectsOutputToContain('Buscando no portal [mobiauto] por: "Honda"...')
            ->expectsOutputToContain('1 veículo(s) encontrado(s). Despachando para a fila...')
            ->expectsOutputToContain('Processo de extração concluído.')
            ->assertExitCode(0);

        Queue::assertPushed(ProcessVehicleETL::class, 1);
    }

    public function test_it_crawls_all_configured_brands_when_keyword_is_omitted(): void
    {
        // Define a small list of brands for the test
        config(['crawler.brands' => ['Honda', 'Toyota']]);
        config(['crawler.delay_between_brands' => 0]); // no delay in tests

        $mockJson = json_encode([
            'props' => [
                'pageProps' => [
                    'deals' => [
                        'results' => [
                            [
                                'id' => 22222,
                                'price' => 85000,
                                'km' => 45000,
                                'trim' => [
                                    'name' => 'Car',
                                    'productionYear' => 2020,
                                    'make' => ['name' => 'Generic'],
                                    'model' => ['name' => 'Car', 'year' => 2020]
                                ],
                                'dealer' => [
                                    'location' => [
                                        'state' => 'SP',
                                        'city' => 'Sao Paulo'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $mockHtml = "<html><body><script id=\"__NEXT_DATA__\" type=\"application/json\">{$mockJson}</script></body></html>";

        Http::fake([
            'www.mobiauto.com.br/comprar/carros/*' => Http::response($mockHtml, 200)
        ]);

        $this->artisan('crawl:vehicles mobiauto')
            ->expectsOutputToContain('Buscando todas as (2) marcas configuradas no portal [mobiauto]...')
            ->expectsOutputToContain('👉 Extraindo marca: [Honda]')
            ->expectsOutputToContain('👉 Extraindo marca: [Toyota]')
            ->assertExitCode(0);

        Queue::assertPushed(ProcessVehicleETL::class, 2);
    }

    public function test_it_fails_when_portal_is_invalid(): void
    {
        $this->artisan('crawl:vehicles invalid_portal')
            ->expectsOutputToContain('❌ Erro: Driver [invalid_portal] não suportado.')
            ->assertExitCode(1);
    }
}

