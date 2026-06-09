<?php

namespace Tests\Feature\Services\Crawlers;

use App\Services\Crawlers\Drivers\MobiautoCrawler;
use App\DTO\RawVehicleData;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MobiautoCrawlerTest extends TestCase
{
    private MobiautoCrawler $crawler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crawler = new MobiautoCrawler();
    }

    public function test_it_returns_source_identifier(): void
    {
        $this->assertEquals('mobiauto', $this->crawler->getSource());
    }

    public function test_it_crawls_and_normalizes_data_successfully(): void
    {
        $mockJson = json_encode([
            'props' => [
                'pageProps' => [
                    'deals' => [
                        'results' => [
                            [
                                'id' => 28979369,
                                'price' => 75990,
                                'km' => 119300,
                                'trim' => [
                                    'name' => 'DX 1.5 (Flex)',
                                    'productionYear' => 2019,
                                    'make' => ['name' => 'Honda'],
                                    'model' => ['name' => 'City', 'year' => 2019]
                                ],
                                'dealer' => [
                                    'location' => [
                                        'state' => 'MG',
                                        'city' => 'Pedro Leopoldo'
                                    ]
                                ]
                            ],
                            [
                                'id' => 29755717,
                                'price' => 120000,
                                'km' => 45000,
                                'trim' => [
                                    'name' => 'Touring 1.5 Turbo CVT',
                                    'productionYear' => 2020,
                                    'make' => ['name' => 'Honda'],
                                    'model' => ['name' => 'Civic', 'year' => 2020]
                                ],
                                'dealer' => [
                                    'location' => [
                                        'state' => 'ES',
                                        'city' => 'Linhares'
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

        $results = $this->crawler->crawl('Honda');

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(RawVehicleData::class, $results);

        // First item asserts
        $this->assertEquals('28979369', $results[0]->externalId);
        $this->assertEquals('mobiauto', $results[0]->source);
        $this->assertEquals('Honda City DX 1.5 (Flex)', $results[0]->title);
        $this->assertEquals('R$ 75.990,00', $results[0]->price);
        $this->assertEquals('119.300 km', $results[0]->km);
        $this->assertEquals('2019/2019', $results[0]->year);
        $this->assertEquals(
            'https://www.mobiauto.com.br/comprar/carros/mg-pedro-leopoldo/honda/city/2019/dx-15-flex/detalhes/28979369?page=detail',
            $results[0]->url
        );

        // Second item asserts
        $this->assertEquals('29755717', $results[1]->externalId);
        $this->assertEquals('Honda Civic Touring 1.5 Turbo CVT', $results[1]->title);
        $this->assertEquals('R$ 120.000,00', $results[1]->price);
        $this->assertEquals('45.000 km', $results[1]->km);
        $this->assertEquals('2020/2020', $results[1]->year);
        $this->assertEquals(
            'https://www.mobiauto.com.br/comprar/carros/es-linhares/honda/civic/2020/touring-15-turbo-cvt/detalhes/29755717?page=detail',
            $results[1]->url
        );

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://www.mobiauto.com.br/comprar/carros/sp-sao-paulo/honda'
                && $request->hasHeader('User-Agent');
        });
    }

    public function test_it_ignores_deals_without_id(): void
    {
        $mockJson = json_encode([
            'props' => [
                'pageProps' => [
                    'deals' => [
                        'results' => [
                            [
                                'price' => 75990,
                                'trim' => [
                                    'name' => 'DX 1.5 (Flex)',
                                    'make' => ['name' => 'Honda'],
                                    'model' => ['name' => 'City']
                                ]
                            ],
                            [
                                'id' => 29755717,
                                'price' => 120000,
                                'trim' => [
                                    'name' => 'Touring 1.5 Turbo CVT',
                                    'make' => ['name' => 'Honda'],
                                    'model' => ['name' => 'Civic']
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

        $results = $this->crawler->crawl('Honda');

        $this->assertCount(1, $results);
        $this->assertEquals('29755717', $results[0]->externalId);
    }

    public function test_it_returns_empty_array_on_http_failure(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn ($message, $context) => str_contains($message, 'Falha HTTP') && $context['status'] === 500);

        Http::fake([
            'www.mobiauto.com.br/comprar/carros/*' => Http::response([], 500)
        ]);

        $results = $this->crawler->crawl('Honda');

        $this->assertEmpty($results);
    }

    public function test_it_returns_empty_array_on_connection_failure(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(fn ($message, $context) => str_contains($message, 'Falha de conexão') && $context['keyword'] === 'Honda');

        Http::fake([
            'www.mobiauto.com.br/comprar/carros/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection timed out')
        ]);

        $results = $this->crawler->crawl('Honda');

        $this->assertEmpty($results);
    }
}

