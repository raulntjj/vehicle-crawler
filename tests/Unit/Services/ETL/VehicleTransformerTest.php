<?php

namespace Tests\Unit\Services\ETL;

use App\Services\ETL\VehicleTransformer;
use PHPUnit\Framework\TestCase;

class VehicleTransformerTest extends TestCase
{
    private VehicleTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new VehicleTransformer();
    }

    public function test_it_cleans_and_transforms_vehicle_data(): void
    {
        $rawData = [
            'external_id' => '12345',
            'source'      => 'mobiauto',
            'brand'       => '  Honda  ',
            'model'       => ' Civic  ',
            'title'       => "  Honda   \n Civic   Sport  ",
            'price'       => 'R$ 145.900,99',
            'km'          => '12.345 km',
            'year'        => '2021/2022',
            'url'         => '  https://example.com/honda-civic-sport   ',
        ];

        $transformed = $this->transformer->transform($rawData);

        $this->assertEquals([
            'external_id'      => '12345',
            'source'           => 'mobiauto',
            'brand'            => 'Honda',
            'model'            => 'Civic',
            'title'            => 'Honda Civic Sport',
            'price'            => 145900.99,
            'km'               => 12345,
            'year_fabrication' => 2021,
            'year_model'       => 2022,
            'url'              => 'https://example.com/honda-civic-sport',
        ], $transformed);
    }


    public function test_it_handles_prices_without_cents(): void
    {
        $rawData = [
            'external_id' => '12345',
            'source'      => 'mobiauto',
            'title'       => 'Honda Civic',
            'price'       => 'R$ 145.000',
            'km'          => '12.345 km',
            'year'        => '2021/2022',
            'url'         => 'https://example.com/honda-civic',
        ];

        $transformed = $this->transformer->transform($rawData);

        $this->assertEquals(145000.00, $transformed['price']);
    }

    public function test_it_handles_years_with_only_one_year_provided(): void
    {
        $rawData = [
            'external_id' => '12345',
            'source'      => 'mobiauto',
            'title'       => 'Honda Civic',
            'price'       => 'R$ 145.000',
            'km'          => '12.345 km',
            'year'        => '2022',
            'url'         => 'https://example.com/honda-civic',
        ];

        $transformed = $this->transformer->transform($rawData);

        $this->assertEquals(2022, $transformed['year_fabrication']);
        $this->assertEquals(2022, $transformed['year_model']);
    }
}
