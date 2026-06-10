<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CrawlPortalJob;
use App\Jobs\CrawlLocationJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CrawlPortalJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_crawl_location_jobs(): void
    {
        Queue::fake();

        config(['crawler.default_locations' => ['sp-sao-paulo', 'mg-belo-horizonte']]);

        $job = new CrawlPortalJob('mobiauto', 'Honda');
        $job->handle();

        Queue::assertPushed(CrawlLocationJob::class, 2);
        Queue::assertPushed(CrawlLocationJob::class, function ($j) {
            return $j->portal === 'mobiauto'
                && $j->location === 'sp-sao-paulo'
                && $j->keyword === 'Honda';
        });
        Queue::assertPushed(CrawlLocationJob::class, function ($j) {
            return $j->portal === 'mobiauto'
                && $j->location === 'mg-belo-horizonte'
                && $j->keyword === 'Honda';
        });
    }
}
