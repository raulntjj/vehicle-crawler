<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CrawlVehicles;
use App\Jobs\ProcessVehicles;
use App\Repositories\Contracts\RawVehicleRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CrawlVehiclesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_crawls_stores_in_mongo_and_dispatches_process_jobs(): void
    {
        Queue::fake();

        $fakeMongoId = '665f1a2b3c4d5e6f7a8b9c0d';

        // Mock do RawVehicleRepository — simula a inserção e retorna um _id
        $mockRawRepo = $this->mock(RawVehicleRepositoryInterface::class);
        $mockRawRepo->shouldReceive('store')
            ->once()
            ->andReturn($fakeMongoId);

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

        $job = new CrawlVehicles('mobiauto', 'sp-sao-paulo', 'Honda');
        $this->app->call([$job, 'handle']);

        Queue::assertPushed(ProcessVehicles::class, 1);
        Queue::assertPushed(ProcessVehicles::class, function ($job) use ($fakeMongoId) {
            return $job->mongoId === $fakeMongoId
                && $job->externalId === '12345';
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

        $job = new CrawlVehicles('mobiauto', 'sp-sao-paulo', 'Honda');
        $this->app->call([$job, 'handle']);

        Queue::assertNotPushed(ProcessVehicles::class);
    }

    public function test_dispatch_for_portal_with_keyword_dispatches_for_all_locations(): void
    {
        Queue::fake();

        config(['crawler.default_locations' => ['sp-sao-paulo', 'mg-belo-horizonte']]);

        $mockBrandRepo = $this->mock(\App\Repositories\Contracts\BrandRepositoryInterface::class);

        CrawlVehicles::dispatchForPortal('mobiauto', 'Honda', $mockBrandRepo);

        Queue::assertPushed(CrawlVehicles::class, 2);
        Queue::assertPushed(CrawlVehicles::class, function ($j) {
            return $j->portal === 'mobiauto'
                && $j->location === 'sp-sao-paulo'
                && $j->brand === 'Honda';
        });
        Queue::assertPushed(CrawlVehicles::class, function ($j) {
            return $j->portal === 'mobiauto'
                && $j->location === 'mg-belo-horizonte'
                && $j->brand === 'Honda';
        });
    }

    public function test_dispatch_for_portal_without_keyword_dispatches_for_all_brands_and_locations(): void
    {
        Queue::fake();

        config([
            'crawler.default_locations' => ['sp-sao-paulo'],
            'crawler.delay_between_brands' => 3,
        ]);

        $mockBrandRepo = $this->mock(\App\Repositories\Contracts\BrandRepositoryInterface::class);
        $mockBrandRepo->shouldReceive('getActiveBrandNames')
            ->once()
            ->andReturn(['Honda', 'Toyota']);

        CrawlVehicles::dispatchForPortal('mobiauto', null, $mockBrandRepo);

        Queue::assertPushed(CrawlVehicles::class, 2);
        Queue::assertPushed(CrawlVehicles::class, function ($j) {
            return $j->brand === 'Honda';
        });
        Queue::assertPushed(CrawlVehicles::class, function ($j) {
            return $j->brand === 'Toyota';
        });
    }
}
