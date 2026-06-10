import { useQuery } from "@tanstack/react-query";
import api from "@/lib/api";
import type { ApiResponse, FilterMetadata } from "@/lib/types";

export function useFilterMetadata() {
  return useQuery({
    queryKey: ["filter-metadata"],
    queryFn: async () => {
      const { data } = await api.get<ApiResponse<FilterMetadata>>(
        "/filters/metadata"
      );
      return data.data;
    },
    staleTime: 1000 * 60 * 5, // 5 minutes — metadata rarely changes
  });
}
