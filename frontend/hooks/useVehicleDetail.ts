import { useQuery } from "@tanstack/react-query";
import api from "@/lib/api";
import type { ApiResponse, VehicleDetail } from "@/lib/types";

export function useVehicleDetail(id: number | null) {
  return useQuery({
    queryKey: ["vehicle-detail", id],
    queryFn: async () => {
      const { data } = await api.get<ApiResponse<VehicleDetail>>(
        `/vehicles/${id}`
      );
      return data.data;
    },
    enabled: id !== null,
  });
}
