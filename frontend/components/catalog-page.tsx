"use client";

import { useState } from "react";
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
  const { data, isLoading, isFetching } = useVehicles();

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
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-primary-foreground">
                  <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2" />
                  <circle cx="7" cy="17" r="2" />
                  <path d="M9 17h6" />
                  <circle cx="17" cy="17" r="2" />
                </svg>
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
