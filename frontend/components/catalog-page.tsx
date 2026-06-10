"use client";

import { useState } from "react";
import { Car } from "lucide-react";
import { useFilterStore } from "@/store/useFilterStore";
import { useVehicles } from "@/hooks/useVehicles";
import { FilterSidebar } from "@/components/filters/filter-sidebar";
import { SearchBar } from "@/components/search/search-bar";
import { VehicleGrid } from "@/components/vehicles/vehicle-grid";
import { VehicleDetailDialog } from "@/components/vehicles/vehicle-detail-dialog";
import { PaginationBar } from "@/components/pagination/pagination-bar";

export function CatalogPage() {
  const [selectedVehicleId, setSelectedVehicleId] = useState<number | null>(null);
  const { setPage } = useFilterStore();
  const { data, isLoading, isFetching, isError } = useVehicles();

  const vehicles = data?.vehicles ?? [];
  const meta = data?.meta;

  return (
    <div className="min-h-dvh flex flex-col">
      {/* ─── Header ───────────────────────────────────────────── */}
      <header className="sticky top-0 z-40 border-b border-border/50 bg-background/80 backdrop-blur-xl supports-[backdrop-filter]:bg-background/60">
        <div className="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex items-center gap-4 mb-4">
            <div className="flex items-center gap-2.5">
              <div className="w-8 h-8 rounded-lg bg-gradient-to-br from-primary to-primary/60 flex items-center justify-center">
                <Car className="w-4 h-4 text-primary-foreground" strokeWidth={2} />
              </div>
              <h1 className="text-lg font-bold tracking-tight">
                <span className="text-primary">Auto</span>Catálogo
              </h1>
            </div>
          </div>
          <SearchBar totalResults={meta?.total} />
        </div>
      </header>

      {/* ─── Main Content ─────────────────────────────────────── */}
      <div className="flex-1 mx-auto max-w-[1440px] w-full px-4 sm:px-6 lg:px-8 py-6">
        <div className="flex gap-8">
          {/* Desktop Sidebar */}
          <aside className="hidden lg:block w-[280px] shrink-0">
            <div className="sticky top-[140px]">
              <FilterSidebar />
            </div>
          </aside>

          {/* Grid + Pagination */}
          <main className="flex-1 min-w-0">
            <div className={isFetching && !isLoading ? "opacity-60 transition-opacity duration-200" : ""}>
              <VehicleGrid
                vehicles={vehicles}
                isLoading={isLoading}
                isError={isError}
                onSelectVehicle={(id) => setSelectedVehicleId(id)}
              />
            </div>

            {meta && (
              <PaginationBar
                currentPage={meta.current_page}
                lastPage={meta.last_page}
                onPageChange={setPage}
              />
            )}
          </main>
        </div>
      </div>

      {/* ─── Detail Modal ─────────────────────────────────────── */}
      <VehicleDetailDialog
        vehicleId={selectedVehicleId}
        onClose={() => setSelectedVehicleId(null)}
      />
    </div>
  );
}
