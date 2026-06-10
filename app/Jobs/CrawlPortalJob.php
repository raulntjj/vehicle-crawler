<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CrawlPortalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * O número de vezes que o job pode ser tentado.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Crie uma nova instância do job.
     */
    public function __construct(
        public string $portal,
        public ?string $keyword = null
    ) {}

    /**
     * Execute o job.
     */
    public function handle(): void
    {
        Log::info("[CrawlPortalJob] Iniciando processamento para portal", [
            'portal'  => $this->portal,
            'keyword' => $this->keyword,
        ]);

        $locations = config('crawler.default_locations', ['sp-sao-paulo']);

        Log::info("[CrawlPortalJob] Despachando " . count($locations) . " localidade(s) para o portal {$this->portal}", [
            'portal'    => $this->portal,
            'locations' => $locations,
        ]);

        foreach ($locations as $location) {
            CrawlLocationJob::dispatch($this->portal, $location, $this->keyword)
                ->onQueue('crawler-locations');
        }
    }
}
