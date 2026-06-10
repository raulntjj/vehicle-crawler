<?php

namespace Tests\Feature\Console\Commands;

use App\Jobs\CrawlPortalJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
        $this->artisan('crawl:vehicles mobiauto Honda')
            ->expectsOutputToContain('Agendando extração para os portais: mobiauto...')
            ->expectsOutputToContain('👉 Portal [mobiauto] despachado para a fila (crawler-portals)')
            ->expectsOutputToContain('Todos os portais foram agendados com sucesso.')
            ->assertExitCode(0);

        Queue::assertPushed(CrawlPortalJob::class, 1);
        Queue::assertPushed(CrawlPortalJob::class, function ($job) {
            return $job->portal === 'mobiauto'
                && $job->keyword === 'Honda';
        });
    }

    public function test_it_crawls_all_configured_brands_when_keyword_is_omitted(): void
    {
        $this->artisan('crawl:vehicles mobiauto')
            ->expectsOutputToContain('Agendando extração para os portais: mobiauto...')
            ->expectsOutputToContain('👉 Portal [mobiauto] despachado para a fila (crawler-portals)')
            ->expectsOutputToContain('Todos os portais foram agendados com sucesso.')
            ->assertExitCode(0);

        Queue::assertPushed(CrawlPortalJob::class, 1);
        Queue::assertPushed(CrawlPortalJob::class, function ($job) {
            return $job->portal === 'mobiauto'
                && $job->keyword === null;
        });
    }

    public function test_it_crawls_all_available_portals_when_portal_is_omitted(): void
    {
        $this->artisan('crawl:vehicles')
            ->expectsOutputToContain('Agendando extração para os portais: mobiauto...')
            ->expectsOutputToContain('👉 Portal [mobiauto] despachado para a fila (crawler-portals)')
            ->expectsOutputToContain('Todos os portais foram agendados com sucesso.')
            ->assertExitCode(0);

        Queue::assertPushed(CrawlPortalJob::class, 1);
        Queue::assertPushed(CrawlPortalJob::class, function ($job) {
            return $job->portal === 'mobiauto'
                && $job->keyword === null;
        });
    }

    public function test_it_fails_when_portal_is_invalid(): void
    {
        $this->artisan('crawl:vehicles invalid_portal')
            ->expectsOutputToContain('❌ Erro: Driver [invalid_portal] não suportado.')
            ->assertExitCode(1);
    }
}

