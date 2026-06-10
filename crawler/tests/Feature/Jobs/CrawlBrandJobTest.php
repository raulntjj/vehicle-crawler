<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CrawlBrandJob;
use App\Jobs\ProcessVehicleETL;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CrawlBrandJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_crawls_and_dispatches_etl_jobs(): void
    {
        Queue::fake();

        $mockJson = json_encode([
            'props' => [
                'pageProps' => [
                    'deals' => [
                        'results' => [
                            [
                                'id' => 12345,
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

        $job = new CrawlBrandJob('mobiauto', 'sp-sao-paulo', 'Honda');
        $this->app->call([$job, 'handle']);

        Queue::assertPushed(ProcessVehicleETL::class, 1);
        Queue::assertPushed(ProcessVehicleETL::class, function ($job) {
            return $job->rawData['external_id'] === '12345'
                && $job->rawData['source'] === 'mobiauto';
        });
    }

    public function test_it_handles_no_vehicles_found(): void
    {
        Queue::fake();

        $mockJson = json_encode([
            'props' => [
                'pageProps' => [
                    'deals' => [
                        'results' => []
                    ]
                ]
            ]
        ]);

        $mockHtml = "<html><body><script id=\"__NEXT_DATA__\" type=\"application/json\">{$mockJson}</script></body></html>";

        Http::fake([
            'www.mobiauto.com.br/comprar/carros/*' => Http::response($mockHtml, 200)
        ]);

        $job = new CrawlBrandJob('mobiauto', 'sp-sao-paulo', 'Honda');
        $this->app->call([$job, 'handle']);

        Queue::assertNotPushed(ProcessVehicleETL::class);
    }
}
