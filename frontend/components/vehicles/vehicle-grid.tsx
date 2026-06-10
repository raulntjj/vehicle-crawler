"use client";

import { AlertCircle, SearchX } from "lucide-react";
import { Skeleton } from "@/components/ui/skeleton";
import { VehicleCard } from "./vehicle-card";
import type { Vehicle } from "@/lib/types";

interface VehicleGridProps {
  vehicles: Vehicle[];
  isLoading: boolean;
  isError?: boolean;
  onSelectVehicle: (id: number) => void;
}

function CardSkeleton() {
  return (
    <div className="rounded-xl border border-border/50 bg-card p-5 space-y-4">
      <div className="flex justify-between">
        <div className="space-y-2 flex-1">
          <Skeleton className="h-3 w-16" />
          <Skeleton className="h-4 w-3/4" />
        </div>
        <Skeleton className="h-5 w-16 rounded-full" />
      </div>
      <Skeleton className="h-6 w-32" />
      <div className="flex gap-4">
        <Skeleton className="h-4 w-20" />
        <Skeleton className="h-4 w-24" />
      </div>
    </div>
  );
}

export function VehicleGrid({ vehicles, isLoading, isError, onSelectVehicle }: VehicleGridProps) {
  if (isLoading) {
    return (
      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        {Array.from({ length: 9 }).map((_, i) => (
          <CardSkeleton key={i} />
        ))}
      </div>
    );
  }

  if (isError) {
    return (
      <div className="flex flex-col items-center justify-center py-20 text-center">
        <div className="w-16 h-16 rounded-full bg-destructive/10 flex items-center justify-center mb-4">
          <AlertCircle className="w-6 h-6 text-destructive" />
        </div>
        <h3 className="text-lg font-semibold text-foreground mb-1">Erro ao carregar veículos</h3>
        <p className="text-sm text-muted-foreground max-w-sm">
          Não foi possível conectar ao servidor. Verifique se o backend está rodando e tente novamente.
        </p>
      </div>
    );
  }

  if (vehicles.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center py-20 text-center">
        <div className="w-16 h-16 rounded-full bg-muted/50 flex items-center justify-center mb-4">
          <SearchX className="w-6 h-6 text-muted-foreground/60" />
        </div>
        <h3 className="text-lg font-semibold text-foreground mb-1">Nenhum veículo encontrado</h3>
        <p className="text-sm text-muted-foreground max-w-sm">
          Tente ajustar os filtros ou a busca para encontrar o que procura.
        </p>
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
      {vehicles.map((vehicle, index) => (
        <VehicleCard
          key={vehicle.id}
          vehicle={vehicle}
          onClick={() => onSelectVehicle(vehicle.id)}
          index={index}
        />
      ))}
    </div>
  );
}
