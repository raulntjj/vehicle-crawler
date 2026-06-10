<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SearchVehiclesRequest;
use App\DTO\VehicleDTO;
use App\Http\Responses\ApiResponse;
use App\Models\Vehicle;
use App\Services\Catalog\VehicleSearchService;

class VehicleController extends Controller
{
    /**
     * Retorna a lista paginada e filtrada de veículos.
     */
    public function index(SearchVehiclesRequest $request, VehicleSearchService $searchService): ApiResponse
    {
        $paginated = $searchService->search($request->validated());

        $items = collect($paginated->items())
            ->map(fn(Vehicle $vehicle) => VehicleDTO::fromModel($vehicle)->toArray())
            ->toArray();

        return ApiResponse::success(
            data: $items,
            meta: [
                'current_page' => $paginated->currentPage(),
                'from'         => $paginated->firstItem(),
                'last_page'    => $paginated->lastPage(),
                'links'        => $paginated->linkCollection()->toArray(),
                'path'         => $paginated->path(),
                'per_page'     => $paginated->perPage(),
                'to'           => $paginated->lastItem(),
                'total'        => $paginated->total(),
            ],
            links: [
                'first' => $paginated->url(1),
                'last'  => $paginated->url($paginated->lastPage()),
                'prev'  => $paginated->previousPageUrl(),
                'next'  => $paginated->nextPageUrl(),
            ]
        );
    }

    /**
     * Retorna os detalhes de um veículo específico, incluindo seu histórico de preços.
     */
    public function show(int $id): ApiResponse
    {
        $vehicle = Vehicle::with('priceHistories')->findOrFail($id);

        $dto = VehicleDTO::fromModel($vehicle, true);

        return ApiResponse::success($dto->toArray());
    }

    /**
     * Retorna metadados para preenchimento dinâmico dos filtros no frontend.
     */
    public function metadata(): ApiResponse
    {
        $brands = Vehicle::distinct()->orderBy('brand')->pluck('brand')->toArray();
        $sources = Vehicle::distinct()->orderBy('source')->pluck('source')->toArray();

        $stats = Vehicle::query()
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
            ->selectRaw('MIN(km) as min_km, MAX(km) as max_km')
            ->selectRaw('MIN(year_fabrication) as min_year, MAX(year_model) as max_year')
            ->first();

        return ApiResponse::success([
            'brands'  => $brands,
            'sources' => $sources,
            'ranges'  => [
                'price' => [
                    'min' => $stats ? (float) ($stats->min_price ?? 0) : 0,
                    'max' => $stats ? (float) ($stats->max_price ?? 0) : 0,
                ],
                'km' => [
                    'min' => $stats ? (int) ($stats->min_km ?? 0) : 0,
                    'max' => $stats ? (int) ($stats->max_km ?? 0) : 0,
                ],
                'year' => [
                    'min' => $stats ? (int) ($stats->min_year ?? 1900) : 1900,
                    'max' => $stats ? (int) ($stats->max_year ?? date('Y') + 1) : (int) date('Y') + 1,
                ],
            ]
        ]);
    }
}
