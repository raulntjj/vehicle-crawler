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
        Http::fake([
            'api.mobiauto.com.br/*' => Http::response([
                'models' => [
                    [
                        'id' => '20411',
                        'makeName' => 'Honda',
                        'name' => 'Civic',
                        'modelYear' => 2022,
                    ],
                    [
                        'id' => '21216',
                        'brandName' => 'Toyota',
                        'modelName' => 'Corolla',
                        'modelYear' => 2023,
                    ]
                ]
            ], 200)
        ]);

        $results = $this->crawler->crawl('Honda');

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(RawVehicleData::class, $results);

        // First model asserts
        $this->assertEquals('20411', $results[0]->externalId);
        $this->assertEquals('mobiauto', $results[0]->source);
        $this->assertEquals('Honda Civic', $results[0]->title);
        $this->assertStringContainsString('R$', $results[0]->price);
        $this->assertStringContainsString('km', $results[0]->km);
        $this->assertStringContainsString('2022', $results[0]->year);
        $this->assertEquals('https://www.mobiauto.com.br/carros/honda-civic/id-20411', $results[0]->url);

        // Second model asserts
        $this->assertEquals('21216', $results[1]->externalId);
        $this->assertEquals('Toyota Corolla', $results[1]->title);
        $this->assertEquals('https://www.mobiauto.com.br/carros/toyota-corolla/id-21216', $results[1]->url);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.mobiauto.com.br/search/api/vehicle/v1.0/open-search?keyword=Honda&vehicleType=CAR&isServer=true'
                && $request->hasHeader('User-Agent');
        });
    }

    public function test_it_ignores_models_without_id(): void
    {
        Http::fake([
            'api.mobiauto.com.br/*' => Http::response([
                'models' => [
                    [
                        'makeName' => 'Honda',
                        'name' => 'Civic',
                    ],
                    [
                        'id' => '21216',
                        'makeName' => 'Toyota',
                        'name' => 'Corolla',
                    ]
                ]
            ], 200)
        ]);

        $results = $this->crawler->crawl('Honda');

        $this->assertCount(1, $results);
        $this->assertEquals('21216', $results[0]->externalId);
    }

    public function test_it_returns_empty_array_on_http_failure(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn ($message, $context) => str_contains($message, 'Falha HTTP') && $context['status'] === 500);

        Http::fake([
            'api.mobiauto.com.br/*' => Http::response([], 500)
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
            'api.mobiauto.com.br/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection timed out')
        ]);

        $results = $this->crawler->crawl('Honda');

        $this->assertEmpty($results);
    }
}
