import { useQuery } from "@tanstack/react-query";
import api from "@/lib/api";
import { useFilterStore } from "@/store/useFilterStore";
import type { ApiResponse, Vehicle, PaginationMeta, PaginationLinks } from "@/lib/types";

interface VehiclesResponse {
  vehicles: Vehicle[];
  meta?: PaginationMeta;
  links?: PaginationLinks;
}

export function useVehicles() {
  const filters = useFilterStore();

  return useQuery({
    queryKey: [
      "vehicles",
      filters.search,
      filters.brands,
      filters.sources,
      filters.model,
      filters.minPrice,
      filters.maxPrice,
      filters.minKm,
      filters.maxKm,
      filters.minYear,
      filters.maxYear,
      filters.orderBy,
      filters.orderDirection,
      filters.perPage,
      filters.page,
    ],
    queryFn: async (): Promise<VehiclesResponse> => {
      const params: Record<string, string | number> = {};

      if (filters.search) params.search = filters.search;
      if (filters.brands.length > 0) params.brands = filters.brands.join(",");
      if (filters.sources.length > 0) params.sources = filters.sources.join(",");
      if (filters.model) params.model = filters.model;
      if (filters.minPrice !== undefined) params.min_price = filters.minPrice;
      if (filters.maxPrice !== undefined) params.max_price = filters.maxPrice;
      if (filters.minKm !== undefined) params.min_km = filters.minKm;
      if (filters.maxKm !== undefined) params.max_km = filters.maxKm;
      if (filters.minYear !== undefined) params.min_year = filters.minYear;
      if (filters.maxYear !== undefined) params.max_year = filters.maxYear;
      if (filters.orderBy) params.order_by = filters.orderBy;
      if (filters.orderDirection) params.order_direction = filters.orderDirection;
      params.per_page = filters.perPage;
      params.page = filters.page;

      const { data } = await api.get<ApiResponse<Vehicle[]>>("/vehicles", {
        params,
      });

      return {
        vehicles: data.data,
        meta: data.meta,
        links: data.links,
      };
    },
    placeholderData: (previousData) => previousData,
  });
}
