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
 * Processador central e ÚNICO do ecossistema de crawlers. Recebe o
 * payload normalizado (Contrato Universal) de qualquer portal e executa:
 *
 *  1. Transform — limpa e converte as strings brutas para os tipos corretos
 *  2. Load      — persiste/atualiza via Eloquent e registra histórico de preços
 *
 * A unicidade de um veículo é definida pelo par (external_id + source),
 * garantindo que IDs iguais de portais diferentes não colidam.
 *
 * Fila: etl-vehicles (RabbitMQ)
 *
 * @see CrawlMobiautoCommand Para o normalização do payload do Mobiauto.
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
        // Chaves de identidade do payload
        $externalId = $this->rawData['external_id'];
        $source     = $this->rawData['source'] ?? 'unknown';

        $logContext = "[{$source}::{$externalId}]";

        Log::info("[ETL] Processando veículo: {$logContext}");

        // -----------------------------------------------------------------
        // TRANSFORM — Limpeza e conversão dos dados brutos
        // -----------------------------------------------------------------
        $cleanPrice           = $this->parsePrice($this->rawData['price']);
        $cleanKm              = $this->parseKm($this->rawData['km']);
        [$yearFab, $yearModel] = $this->parseYear($this->rawData['year']);

        $transformedData = [
            'source'           => $source,
            'title'            => $this->cleanTitle($this->rawData['title']),
            'price'            => $cleanPrice,
            'km'               => $cleanKm,
            'year_fabrication' => $yearFab,
            'year_model'       => $yearModel,
            'url'              => trim($this->rawData['url']),
        ];

        Log::info("[ETL] Dados transformados para {$logContext}:", $transformedData);

        // -----------------------------------------------------------------
        // LOAD — Persistência no banco de dados
        // -----------------------------------------------------------------

        // Chave composta: garante que o mesmo external_id de portais
        // distintos não colida (ex: ID 123 da Mobiauto ≠ ID 123 da Webmotors)
        $uniqueKey = ['external_id' => $externalId, 'source' => $source];

        // Busca o veículo existente para verificar mudança de preço
        $existingVehicle = Vehicle::where($uniqueKey)->first();

        // Cria ou atualiza o registro do veículo
        $vehicle = Vehicle::updateOrCreate($uniqueKey, $transformedData);

        // -----------------------------------------------------------------
        // Histórico de Preços
        //
        // Registra se:
        // a) É um veículo novo (não existia antes)
        // b) O preço mudou em relação ao registro anterior
        // -----------------------------------------------------------------
        $isNewVehicle = $existingVehicle === null;
        $priceChanged = ! $isNewVehicle && (float) $existingVehicle->price !== $cleanPrice;

        if ($isNewVehicle || $priceChanged) {
            PriceHistory::create([
                'vehicle_id' => $vehicle->id,
                'price'      => $cleanPrice,
            ]);

            $action = $isNewVehicle ? 'NOVO' : 'PREÇO ALTERADO';
            Log::info("[ETL] [{$action}] Histórico registrado para {$logContext}: R\$ {$cleanPrice}");
        } else {
            Log::info("[ETL] Preço inalterado para {$logContext}. Sem registro no histórico.");
        }

        Log::info("[ETL] ✅ Veículo {$logContext} processado com sucesso.");
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
