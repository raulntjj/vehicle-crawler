<?php

namespace App\Jobs;

use App\Models\PriceHistory;
use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job: ProcessVehicleETL
 *
 * [TRANSFORM & LOAD] — Etapas 2 e 3 do pipeline ETL.
 *
 * Recebe os dados brutos de um anúncio de veículo (strings "sujas"),
 * executa a limpeza e transformação dos campos, e persiste no banco
 * de dados usando Eloquent.
 *
 * Se o veículo já existe (mesmo external_id) e o preço mudou,
 * registra a alteração na tabela de histórico de preços.
 *
 * Fila: etl-vehicles (RabbitMQ)
 */
class ProcessVehicleETL implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número máximo de tentativas em caso de falha.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Dados brutos do anúncio (vindos do crawler).
     *
     * @var array<string, string>
     */
    private array $rawData;

    /**
     * Cria uma nova instância do Job.
     *
     * @param array<string, string> $rawData Dados brutos extraídos do HTML
     */
    public function __construct(array $rawData)
    {
        $this->rawData = $rawData;
    }

    /**
     * Execução do Job — Transform & Load.
     *
     * 1. Transforma/limpa os dados brutos
     * 2. Persiste ou atualiza o veículo (updateOrCreate)
     * 3. Registra histórico de preço se houve alteração ou é novo
     */
    public function handle(): void
    {
        $externalId = $this->rawData['external_id'];

        Log::info("[ETL] Processando veículo: {$externalId}");

        // -----------------------------------------------------------------
        // TRANSFORM — Limpeza e conversão dos dados brutos
        // -----------------------------------------------------------------
        $cleanPrice           = $this->parsePrice($this->rawData['price']);
        $cleanKm              = $this->parseKm($this->rawData['km']);
        [$yearFab, $yearModel] = $this->parseYear($this->rawData['year']);

        $transformedData = [
            'title'            => $this->cleanTitle($this->rawData['title']),
            'price'            => $cleanPrice,
            'km'               => $cleanKm,
            'year_fabrication' => $yearFab,
            'year_model'       => $yearModel,
            'url'              => trim($this->rawData['url']),
        ];

        Log::info("[ETL] Dados transformados para {$externalId}:", $transformedData);

        // -----------------------------------------------------------------
        // LOAD — Persistência no banco de dados
        // -----------------------------------------------------------------

        // Busca o veículo existente para verificar mudança de preço
        $existingVehicle = Vehicle::where('external_id', $externalId)->first();

        // Cria ou atualiza o registro do veículo
        $vehicle = Vehicle::updateOrCreate(
            ['external_id' => $externalId],
            $transformedData
        );

        // -----------------------------------------------------------------
        // Histórico de Preços
        //
        // Registra se:
        // a) É um veículo novo (não existia antes)
        // b) O preço mudou em relação ao registro anterior
        // -----------------------------------------------------------------
        $isNewVehicle  = $existingVehicle === null;
        $priceChanged  = ! $isNewVehicle && (float) $existingVehicle->price !== $cleanPrice;

        if ($isNewVehicle || $priceChanged) {
            PriceHistory::create([
                'vehicle_id' => $vehicle->id,
                'price'      => $cleanPrice,
            ]);

            $action = $isNewVehicle ? 'NOVO' : 'PREÇO ALTERADO';
            Log::info("[ETL] [{$action}] Histórico de preço registrado para {$externalId}: R\$ {$cleanPrice}");
        } else {
            Log::info("[ETL] Preço inalterado para {$externalId}. Sem registro no histórico.");
        }

        Log::info("[ETL] ✅ Veículo {$externalId} processado com sucesso.");
    }

    // =========================================================================
    // Métodos de Transformação (Transform)
    // =========================================================================

    /**
     * Limpa o título removendo espaços excessivos.
     *
     * Entrada:  "  Volkswagen Polo Highline 200 TSI   "
     * Saída:    "Volkswagen Polo Highline 200 TSI"
     */
    private function cleanTitle(string $rawTitle): string
    {
        // Remove espaços duplicados e trim
        return preg_replace('/\s+/', ' ', trim($rawTitle));
    }

    /**
     * Converte string de preço brasileiro para float.
     *
     * Entrada:  "R$ 95.900,00" ou "R$98.750,50" ou " R$   89.500,00 "
     * Saída:    95900.00 ou 98750.50 ou 89500.00
     *
     * Lógica:
     * 1. Remove "R$" e espaços
     * 2. Remove pontos (separador de milhar brasileiro)
     * 3. Troca vírgula por ponto (separador decimal)
     */
    private function parsePrice(string $rawPrice): float
    {
        $cleaned = $rawPrice;

        // Remove o símbolo de moeda e espaços
        $cleaned = preg_replace('/R\$\s*/', '', $cleaned);

        // Remove espaços restantes
        $cleaned = trim($cleaned);

        // Remove pontos (separador de milhar)
        $cleaned = str_replace('.', '', $cleaned);

        // Troca vírgula por ponto (decimal)
        $cleaned = str_replace(',', '.', $cleaned);

        return (float) $cleaned;
    }

    /**
     * Converte string de quilometragem para inteiro.
     *
     * Entrada:  "24.500 km" ou "12.100km" ou " 45.230 km "
     * Saída:    24500 ou 12100 ou 45230
     *
     * Lógica:
     * 1. Remove o sufixo "km" (case-insensitive)
     * 2. Remove pontos (separador de milhar)
     * 3. Converte para inteiro
     */
    private function parseKm(string $rawKm): int
    {
        $cleaned = $rawKm;

        // Remove "km" (case insensitive) e espaços
        $cleaned = preg_replace('/\s*km\s*/i', '', $cleaned);

        // Remove pontos (separador de milhar)
        $cleaned = str_replace('.', '', $cleaned);

        return (int) trim($cleaned);
    }

    /**
     * Separa o ano no formato "fabricação/modelo" em dois inteiros.
     *
     * Entrada:  "2022/2023" ou " 2021/2022 "
     * Saída:    [2022, 2023] ou [2021, 2022]
     *
     * @return array{0: int, 1: int} [ano_fabricação, ano_modelo]
     */
    private function parseYear(string $rawYear): array
    {
        $cleaned = trim($rawYear);

        // Divide pelo separador "/"
        $parts = explode('/', $cleaned);

        $yearFabrication = (int) trim($parts[0] ?? 0);
        $yearModel       = (int) trim($parts[1] ?? $yearFabrication);

        return [$yearFabrication, $yearModel];
    }
}
