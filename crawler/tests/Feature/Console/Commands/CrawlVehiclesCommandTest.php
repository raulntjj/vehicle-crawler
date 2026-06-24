<?php

namespace Tests\Feature\Console\Commands;

use App\Jobs\CrawlVehicles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CrawlVehiclesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_it_crawls_single_brand_when_keyword_is_provided(): void
    {
        config(['crawler.default_locations' => ['sp-sao-paulo', 'mg-belo-horizonte']]);

        $this->artisan('crawl:vehicles mobiauto Honda')
            ->expectsOutputToContain('Agendando extração para os portais: mobiauto...')
            ->expectsOutputToContain('👉 Portal [mobiauto] despachado para a fila (crawler-portals)')
            ->expectsOutputToContain('Todos os portais foram agendados com sucesso.')
            ->assertExitCode(0);

        Queue::assertPushed(CrawlVehicles::class, 2);
        Queue::assertPushed(CrawlVehicles::class, function ($job) {
            return $job->portal === 'mobiauto'
                && $job->brand === 'Honda'
                && $job->location === 'sp-sao-paulo';
        });
        Queue::assertPushed(CrawlVehicles::class, function ($job) {
            return $job->portal === 'mobiauto'
                && $job->brand === 'Honda'
                && $job->location === 'mg-belo-horizonte';
        });
    }

    public function test_it_fails_when_portal_is_invalid(): void
    {
        $this->artisan('crawl:vehicles invalid_portal')
            ->expectsOutputToContain('❌ Erro: Driver [invalid_portal] não suportado.')
            ->assertExitCode(1);
    }
}
