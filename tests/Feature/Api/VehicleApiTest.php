<?php

namespace Tests\Feature\Api;

use App\Models\Vehicle;
use App\Models\PriceHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar veículos de teste
        $v1 = Vehicle::create([
            'external_id'      => '1001',
            'brand'            => 'Honda',
            'model'            => 'Civic',
            'title'            => 'Honda Civic LX 2018',
            'price'            => 85000.00,
            'km'               => 60000,
            'year_fabrication' => 2018,
            'year_model'       => 2018,
            'url'              => 'http://example.com/1001',
            'source'           => 'mobiauto',
        ]);

        $v2 = Vehicle::create([
            'external_id'      => '1002',
            'brand'            => 'Toyota',
            'model'            => 'Corolla',
            'title'            => 'Toyota Corolla XEi 2020',
            'price'            => 110000.00,
            'km'               => 30000,
            'year_fabrication' => 2019,
            'year_model'       => 2020,
            'url'              => 'http://example.com/1002',
            'source'           => 'webmotors',
        ]);

        $v3 = Vehicle::create([
            'external_id'      => '1003',
            'brand'            => 'Honda',
            'model'            => 'Fit',
            'title'            => 'Honda Fit EXL 2015',
            'price'            => 55000.00,
            'km'               => 95000,
            'year_fabrication' => 2015,
            'year_model'       => 2015,
            'url'              => 'http://example.com/1003',
            'source'           => 'mobiauto',
        ]);

        // Registrar histórico de preço para o v1 com datas específicas (ignorando fillable)
        $p1 = new PriceHistory();
        $p1->vehicle_id = $v1->id;
        $p1->price = 88000.00;
        $p1->created_at = now()->subDays(2);
        $p1->save();

        $p2 = new PriceHistory();
        $p2->vehicle_id = $v1->id;
        $p2->price = 85000.00;
        $p2->created_at = now();
        $p2->save();
    }

    public function test_it_lists_vehicles_with_default_pagination(): void
    {
        $response = $this->getJson('/api/vehicles');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'external_id',
                        'brand',
                        'model',
                        'title',
                        'price',
                        'price_formatted',
                        'km',
                        'km_formatted',
                        'year_fabrication',
                        'year_model',
                        'year_formatted',
                        'url',
                        'source',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta'  => ['current_page', 'from', 'last_page', 'links', 'path', 'per_page', 'to', 'total']
            ]);
    }

    public function test_it_filters_by_search_keyword_case_insensitively(): void
    {
        // Busca por "civic" (minúsculo)
        $response = $this->getJson('/api/vehicles?search=civic');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.model', 'Civic');

        // Busca por "HONDA" (maiúsculo)
        $response2 = $this->getJson('/api/vehicles?search=HONDA');
        $response2->assertStatus(200)
            ->assertJsonCount(2, 'data'); // Civic e Fit
    }

    public function test_it_filters_by_multiple_brands_comma_separated(): void
    {
        $response = $this->getJson('/api/vehicles?brands=honda,toyota');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        $responseOnlyToyota = $this->getJson('/api/vehicles?brands=toyota');
        $responseOnlyToyota->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.brand', 'Toyota');
    }

    public function test_it_filters_by_price_range(): void
    {
        $response = $this->getJson('/api/vehicles?min_price=60000&max_price=100000');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.external_id', '1001'); // Honda Civic (85k)
    }

    public function test_it_filters_by_mileage_range(): void
    {
        $response = $this->getJson('/api/vehicles?max_km=50000');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.external_id', '1002'); // Corolla (30k)
    }

    public function test_it_filters_by_year_range(): void
    {
        $response = $this->getJson('/api/vehicles?min_year=2018&max_year=2019');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.external_id', '1001'); // Civic
    }

    public function test_it_orders_vehicles_by_price_ascending(): void
    {
        $response = $this->getJson('/api/vehicles?order_by=price&order_direction=asc');

        $response->assertStatus(200);
        
        $prices = collect($response->json('data'))->pluck('price')->toArray();
        $this->assertEquals([55000.00, 85000.00, 110000.00], $prices);
    }

    public function test_it_orders_vehicles_by_mileage_descending(): void
    {
        $response = $this->getJson('/api/vehicles?order_by=km&order_direction=desc');

        $response->assertStatus(200);

        $kms = collect($response->json('data'))->pluck('km')->toArray();
        $this->assertEquals([95000, 60000, 30000], $kms);
    }

    public function test_it_validates_sorting_parameters(): void
    {
        $response = $this->getJson('/api/vehicles?order_by=invalid_field');
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_by']);

        $response2 = $this->getJson('/api/vehicles?order_direction=invalid_direction');
        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['order_direction']);
    }

    public function test_it_shows_single_vehicle_with_price_history(): void
    {
        $vehicle = Vehicle::where('external_id', '1001')->firstOrFail();

        $response = $this->getJson("/api/vehicles/{$vehicle->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.external_id', '1001')
            ->assertJsonCount(2, 'data.price_history')
            ->assertJsonStructure([
                'data' => [
                    'price_history' => [
                        '*' => [
                            'price',
                            'price_formatted',
                            'date',
                            'date_formatted'
                        ]
                    ]
                ]
            ]);

        // Verifica ordenação decrescente de data no histórico
        $historyPrices = collect($response->json('data.price_history'))->pluck('price')->toArray();
        $this->assertEquals([85000.00, 88000.00], $historyPrices);
    }

    public function test_it_returns_404_for_non_existent_vehicle(): void
    {
        $response = $this->getJson('/api/vehicles/999999');
        $response->assertStatus(404);
    }

    public function test_it_returns_filters_metadata_ranges_and_options(): void
    {
        $response = $this->getJson('/api/filters/metadata');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'brands'  => ['Honda', 'Toyota'],
                    'sources' => ['mobiauto', 'webmotors'],
                    'ranges'  => [
                        'price' => [
                            'min' => 55000.00,
                            'max' => 110000.00,
                        ],
                        'km' => [
                            'min' => 30000,
                            'max' => 95000,
                        ],
                        'year' => [
                            'min' => 2015,
                            'max' => 2020,
                        ]
                    ]
                ]
            ]);
    }
}
