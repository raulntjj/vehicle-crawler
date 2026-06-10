<?php

namespace Tests\Feature\Services\Crawlers;

use App\Services\Crawlers\CrawlerManager;
use App\Services\Crawlers\Drivers\MobiautoCrawler;
use InvalidArgumentException;
use Tests\TestCase;

class CrawlerManagerTest extends TestCase
{
    private CrawlerManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new CrawlerManager();
    }

    public function test_it_resolves_mobiauto_driver_correctly(): void
    {
        $driver = $this->manager->driver('mobiauto');

        $this->assertInstanceOf(MobiautoCrawler::class, $driver);
    }

    public function test_it_resolves_mobiauto_driver_case_insensitively(): void
    {
        $driver = $this->manager->driver('Mobiauto');

        $this->assertInstanceOf(MobiautoCrawler::class, $driver);
    }

    public function test_it_throws_exception_for_unsupported_driver(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Driver [invalid] não suportado. Drivers disponíveis: mobiauto');

        $this->manager->driver('invalid');
    }
}
