<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CrawlLocationJob;
use App\Jobs\CrawlBrandJob;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CrawlLocationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_crawl_brand_job_immediately_when_keyword_is_provided(): void
    {
        Queue::fake();

        $job = new CrawlLocationJob('mobiauto', 'sp-sao-paulo', 'Honda');
        $job->handle();

        Queue::assertPushed(CrawlBrandJob::class, 1);
        Queue::assertPushed(CrawlBrandJob::class, function ($j) {
            return $j->portal === 'mobiauto'
                && $j->location === 'sp-sao-paulo'
                && $j->brand === 'Honda';
        });
    }

    public function test_it_dispatches_delayed_crawl_brand_jobs_for_all_active_brands_when_keyword_is_null(): void
    {
        Queue::fake();

        Brand::create(['name' => 'Honda', 'is_active' => true]);
        Brand::create(['name' => 'Toyota', 'is_active' => true]);
        Brand::create(['name' => 'Ford', 'is_active' => false]); // Inactive brand should not run

        config(['crawler.delay_between_brands' => 3]);

        $job = new CrawlLocationJob('mobiauto', 'sp-sao-paulo', null);
        $job->handle();

        Queue::assertPushed(CrawlBrandJob::class, 2);
        
        // Brand 1: Honda (immediate, delay = 0)
        Queue::assertPushed(CrawlBrandJob::class, function ($j) {
            return $j->brand === 'Honda' && $j->delay === null;
        });

        // Brand 2: Toyota (delay = 3 seconds)
        Queue::assertPushed(CrawlBrandJob::class, function ($j) {
            return $j->brand === 'Toyota' && $j->delay !== null;
        });
    }
}
