<?php

namespace App\Jobs;

use App\Models\Brand;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CrawlLocationJob implements ShouldQueue
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
        public string $location,
        public ?string $keyword = null
    ) {}

    /**
     * Execute o job.
     */
    public function handle(): void
    {
        Log::info("[CrawlLocationJob] Iniciando processamento para localidade", [
            'portal'   => $this->portal,
            'location' => $this->location,
            'keyword'  => $this->keyword,
        ]);

        if ($this->keyword !== null) {
            // Busca apenas o termo específico de forma imediata
            CrawlBrandJob::dispatch($this->portal, $this->location, $this->keyword)
                ->onQueue('crawler-brands');

            Log::info("[CrawlLocationJob] Termo único despachado imediatamente", [
                'portal'   => $this->portal,
                'location' => $this->location,
                'keyword'  => $this->keyword,
            ]);
            return;
        }

        // Busca todas as marcas ativas no banco de dados
        $brands = Brand::where('is_active', true)->pluck('name')->toArray();

        if (empty($brands)) {
            Log::warning("[CrawlLocationJob] Nenhuma marca ativa configurada no banco de dados.");
            return;
        }

        $delay = (int) config('crawler.delay_between_brands', 2);
        
        Log::info("[CrawlLocationJob] Despachando " . count($brands) . " marca(s) com delay configurado de {$delay}s", [
            'portal'   => $this->portal,
            'location' => $this->location,
        ]);

        foreach ($brands as $index => $brand) {
            $delaySeconds = $index * $delay;

            $job = CrawlBrandJob::dispatch($this->portal, $this->location, $brand)
                ->onQueue('crawler-brands');

            if ($delaySeconds > 0) {
                $job->delay(now()->addSeconds($delaySeconds));
            }
        }
    }
}
