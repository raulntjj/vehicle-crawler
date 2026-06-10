"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { useFilterStore } from "@/store/useFilterStore";
import { FilterSheet } from "@/components/filters/filter-sheet";

interface SearchBarProps {
  totalResults?: number;
}

export function SearchBar({ totalResults }: SearchBarProps) {
  const store = useFilterStore();
  const [localSearch, setLocalSearch] = useState(store.search);
  const debounceRef = useRef<ReturnType<typeof setTimeout>>(null);

  // Sync localSearch when store resets
  useEffect(() => {
    setLocalSearch(store.search);
  }, [store.search]);

  const handleSearchChange = useCallback(
    (value: string) => {
      setLocalSearch(value);
      if (debounceRef.current) clearTimeout(debounceRef.current);
      debounceRef.current = setTimeout(() => {
        store.setSearch(value);
      }, 300);
    },
    [store]
  );

  const handleSortChange = useCallback(
    (value: string) => {
      const [orderBy, orderDirection] = value.split(":");
      store.setOrderBy(orderBy);
      store.setOrderDirection(orderDirection as "asc" | "desc");
    },
    [store]
  );

  const currentSort = `${store.orderBy}:${store.orderDirection}`;

  return (
    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div className="flex items-center gap-3 flex-1">
        <FilterSheet />
        <div className="relative flex-1 max-w-md">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground w-4 h-4" />
          <Input
            type="text"
            placeholder="Buscar por marca, modelo ou título..."
            value={localSearch}
            onChange={(e) => handleSearchChange(e.target.value)}
            className="pl-10 h-10 bg-card border-border/50 placeholder:text-muted-foreground/60"
          />
        </div>
        {totalResults !== undefined && (
          <span className="hidden sm:inline-flex text-sm text-muted-foreground whitespace-nowrap">
            <strong className="text-foreground font-semibold">{totalResults.toLocaleString("pt-BR")}</strong>
            &nbsp;veículo{totalResults !== 1 ? "s" : ""}
          </span>
        )}
      </div>

      <div className="flex items-center gap-3">
        {totalResults !== undefined && (
          <span className="sm:hidden text-sm text-muted-foreground">
            <strong className="text-foreground font-semibold">{totalResults.toLocaleString("pt-BR")}</strong>
            &nbsp;resultado{totalResults !== 1 ? "s" : ""}
          </span>
        )}
        <Select value={currentSort} onValueChange={handleSortChange}>
          <SelectTrigger className="w-[200px] h-10 bg-card border-border/50 text-sm">
            <SelectValue placeholder="Ordenar por..." />
          </SelectTrigger>
          <SelectContent className="bg-popover border-border/50">
            <SelectItem value="created_at:desc">Mais recentes</SelectItem>
            <SelectItem value="created_at:asc">Mais antigos</SelectItem>
            <SelectItem value="price:asc">Menor preço</SelectItem>
            <SelectItem value="price:desc">Maior preço</SelectItem>
            <SelectItem value="km:asc">Menor km</SelectItem>
            <SelectItem value="km:desc">Maior km</SelectItem>
            <SelectItem value="year_model:desc">Ano mais novo</SelectItem>
            <SelectItem value="year_model:asc">Ano mais antigo</SelectItem>
            <SelectItem value="brand:asc">Marca A-Z</SelectItem>
            <SelectItem value="brand:desc">Marca Z-A</SelectItem>
          </SelectContent>
        </Select>
      </div>
    </div>
  );
}
