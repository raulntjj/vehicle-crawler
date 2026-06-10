"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { NumericFormat } from "react-number-format";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import { Checkbox } from "@/components/ui/checkbox";
import { Slider } from "@/components/ui/slider";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Separator } from "@/components/ui/separator";
import { useFilterStore } from "@/store/useFilterStore";
import { useFilterMetadata } from "@/hooks/useFilterMetadata";

const INPUT_CLASS =
  "h-8 w-full flex-1 rounded-md border border-border/50 bg-muted/20 px-2 text-center text-xs focus:outline-none focus:ring-1 focus:ring-ring";

export function FilterSidebar() {
  const { data: metadata, isLoading } = useFilterMetadata();
  const store = useFilterStore();

  const [priceRange, setPriceRange] = useState<[number, number]>([0, 0]);
  const [kmRange, setKmRange] = useState<[number, number]>([0, 0]);
  const [yearRange, setYearRange] = useState<[number, number]>([0, 0]);

  useEffect(() => {
    if (!metadata) return;
    setPriceRange([
      store.minPrice ?? metadata.ranges.price.min,
      store.maxPrice ?? metadata.ranges.price.max,
    ]);
    setKmRange([
      store.minKm ?? metadata.ranges.km.min,
      store.maxKm ?? metadata.ranges.km.max,
    ]);
    setYearRange([
      store.minYear ?? metadata.ranges.year.min,
      store.maxYear ?? metadata.ranges.year.max,
    ]);
  }, [metadata, store.minPrice, store.maxPrice, store.minKm, store.maxKm, store.minYear, store.maxYear]);

  const commitPrice = useCallback(
    (min: number, max: number) => {
      if (!metadata) return;
      store.setPriceRange(
        min === metadata.ranges.price.min ? undefined : min,
        max === metadata.ranges.price.max ? undefined : max,
      );
    },
    [metadata, store],
  );

  const commitKm = useCallback(
    (min: number, max: number) => {
      if (!metadata) return;
      store.setKmRange(
        min === metadata.ranges.km.min ? undefined : min,
        max === metadata.ranges.km.max ? undefined : max,
      );
    },
    [metadata, store],
  );

  const commitYear = useCallback(
    (min: number, max: number) => {
      if (!metadata) return;
      store.setYearRange(
        min === metadata.ranges.year.min ? undefined : min,
        max === metadata.ranges.year.max ? undefined : max,
      );
    },
    [metadata, store],
  );

  const handleReset = useCallback(() => {
    store.resetFilters();
    if (!metadata) return;
    setPriceRange([metadata.ranges.price.min, metadata.ranges.price.max]);
    setKmRange([metadata.ranges.km.min, metadata.ranges.km.max]);
    setYearRange([metadata.ranges.year.min, metadata.ranges.year.max]);
  }, [store, metadata]);

  const hasActiveFilters = useMemo(
    () =>
      store.brands.length > 0 ||
      store.sources.length > 0 ||
      store.minPrice !== undefined ||
      store.maxPrice !== undefined ||
      store.minKm !== undefined ||
      store.maxKm !== undefined ||
      store.minYear !== undefined ||
      store.maxYear !== undefined,
    [store.brands, store.sources, store.minPrice, store.maxPrice, store.minKm, store.maxKm, store.minYear, store.maxYear],
  );

  if (isLoading) {
    return (
      <div className="space-y-6 p-1">
        {Array.from({ length: 4 }).map((_, i) => (
          <div key={i} className="space-y-3">
            <Skeleton className="h-5 w-24" />
            <Skeleton className="h-10 w-full" />
            <Skeleton className="h-10 w-full" />
          </div>
        ))}
      </div>
    );
  }

  if (!metadata) return null;

  return (
    <div className="flex flex-col h-full">
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-sm font-semibold uppercase tracking-wider text-muted-foreground">
          Filtros
        </h2>
        {hasActiveFilters && (
          <Button
            variant="ghost"
            size="sm"
            onClick={handleReset}
            className="text-xs text-primary hover:text-primary/80 h-auto py-1 px-2"
          >
            Limpar tudo
          </Button>
        )}
      </div>

      <ScrollArea className="flex-1 -mr-3 pr-3">
        <Accordion
          type="multiple"
          defaultValue={["brands", "price", "km", "year", "sources"]}
          className="space-y-1"
        >
          {/* ─── Brands ──────────────────────────────────────────── */}
          <AccordionItem value="brands" className="border-border/50">
            <AccordionTrigger className="text-sm font-medium py-3 hover:no-underline">
              <div className="flex">
                Marcas
                {store.brands.length > 0 && (
                  <span className="ml-2 rounded-full bg-primary/20 px-2 py-0.5 text-[10px] font-semibold text-primary">
                    {store.brands.length}
                  </span>
                )}
              </div>
            </AccordionTrigger>
            <AccordionContent>
              <div className="space-y-2 pt-1">
                {metadata.brands.map((brand) => (
                  <label key={brand} className="flex items-center gap-2.5 cursor-pointer group">
                    <Checkbox
                      checked={store.brands.includes(brand)}
                      onCheckedChange={() => store.toggleBrand(brand)}
                      className="data-[state=checked]:bg-primary data-[state=checked]:border-primary"
                    />
                    <span className="text-sm text-muted-foreground group-hover:text-foreground transition-colors">
                      {brand}
                    </span>
                  </label>
                ))}
              </div>
            </AccordionContent>
          </AccordionItem>

          {/* ─── Price Range ─────────────────────────────────────── */}
          <AccordionItem value="price" className="border-border/50">
            <AccordionTrigger className="text-sm font-medium py-3 hover:no-underline">
              Preço
            </AccordionTrigger>
            <AccordionContent>
              <div className="space-y-4 pt-2 px-1">
                <Slider
                  min={metadata.ranges.price.min}
                  max={metadata.ranges.price.max}
                  step={1000}
                  value={priceRange}
                  onValueChange={(v) => setPriceRange(v as [number, number])}
                  onValueCommit={(v) => commitPrice(v[0], v[1])}
                  className="w-full"
                />
                <div className="flex items-center gap-2">
                  <NumericFormat
                    value={priceRange[0]}
                    thousandSeparator="."
                    decimalSeparator=","
                    prefix="R$ "
                    decimalScale={0}
                    placeholder="Mínimo"
                    onValueChange={({ floatValue }) => {
                      if (floatValue == null) return;
                      const clamped = Math.max(metadata.ranges.price.min, Math.min(floatValue, priceRange[1]));
                      setPriceRange([clamped, priceRange[1]]);
                    }}
                    onBlur={() => commitPrice(priceRange[0], priceRange[1])}
                    onKeyDown={(e) => e.key === "Enter" && e.currentTarget.blur()}
                    className={INPUT_CLASS}
                  />
                  <span className="text-muted-foreground text-xs shrink-0">—</span>
                  <NumericFormat
                    value={priceRange[1]}
                    thousandSeparator="."
                    decimalSeparator=","
                    prefix="R$ "
                    decimalScale={0}
                    placeholder="Máximo"
                    onValueChange={({ floatValue }) => {
                      if (floatValue == null) return;
                      const clamped = Math.max(priceRange[0], Math.min(floatValue, metadata.ranges.price.max));
                      setPriceRange([priceRange[0], clamped]);
                    }}
                    onBlur={() => commitPrice(priceRange[0], priceRange[1])}
                    onKeyDown={(e) => e.key === "Enter" && e.currentTarget.blur()}
                    className={INPUT_CLASS}
                  />
                </div>
              </div>
            </AccordionContent>
          </AccordionItem>

          {/* ─── KM Range ────────────────────────────────────────── */}
          <AccordionItem value="km" className="border-border/50">
            <AccordionTrigger className="text-sm font-medium py-3 hover:no-underline">
              Quilometragem
            </AccordionTrigger>
            <AccordionContent>
              <div className="space-y-4 pt-2 px-1">
                <Slider
                  min={metadata.ranges.km.min}
                  max={metadata.ranges.km.max}
                  step={1000}
                  value={kmRange}
                  onValueChange={(v) => setKmRange(v as [number, number])}
                  onValueCommit={(v) => commitKm(v[0], v[1])}
                  className="w-full"
                />
                <div className="flex items-center gap-2">
                  <NumericFormat
                    value={kmRange[0]}
                    thousandSeparator="."
                    decimalSeparator=","
                    suffix=" km"
                    decimalScale={0}
                    placeholder="Mínimo"
                    onValueChange={({ floatValue }) => {
                      if (floatValue == null) return;
                      const clamped = Math.max(metadata.ranges.km.min, Math.min(floatValue, kmRange[1]));
                      setKmRange([clamped, kmRange[1]]);
                    }}
                    onBlur={() => commitKm(kmRange[0], kmRange[1])}
                    onKeyDown={(e) => e.key === "Enter" && e.currentTarget.blur()}
                    className={INPUT_CLASS}
                  />
                  <span className="text-muted-foreground text-xs shrink-0">—</span>
                  <NumericFormat
                    value={kmRange[1]}
                    thousandSeparator="."
                    decimalSeparator=","
                    suffix=" km"
                    decimalScale={0}
                    placeholder="Máximo"
                    onValueChange={({ floatValue }) => {
                      if (floatValue == null) return;
                      const clamped = Math.max(kmRange[0], Math.min(floatValue, metadata.ranges.km.max));
                      setKmRange([kmRange[0], clamped]);
                    }}
                    onBlur={() => commitKm(kmRange[0], kmRange[1])}
                    onKeyDown={(e) => e.key === "Enter" && e.currentTarget.blur()}
                    className={INPUT_CLASS}
                  />
                </div>
              </div>
            </AccordionContent>
          </AccordionItem>

          {/* ─── Year Range ──────────────────────────────────────── */}
          <AccordionItem value="year" className="border-border/50">
            <AccordionTrigger className="text-sm font-medium py-3 hover:no-underline">
              Ano
            </AccordionTrigger>
            <AccordionContent>
              <div className="space-y-4 pt-2 px-1">
                <Slider
                  min={metadata.ranges.year.min}
                  max={metadata.ranges.year.max}
                  step={1}
                  value={yearRange}
                  onValueChange={(v) => setYearRange(v as [number, number])}
                  onValueCommit={(v) => commitYear(v[0], v[1])}
                  className="w-full"
                />
                <div className="flex items-center gap-2">
                  <NumericFormat
                    value={yearRange[0]}
                    decimalScale={0}
                    allowNegative={false}
                    placeholder="Mínimo"
                    onValueChange={({ floatValue }) => {
                      if (floatValue == null) return;
                      const clamped = Math.max(metadata.ranges.year.min, Math.min(floatValue, yearRange[1]));
                      setYearRange([clamped, yearRange[1]]);
                    }}
                    onBlur={() => commitYear(yearRange[0], yearRange[1])}
                    onKeyDown={(e) => e.key === "Enter" && e.currentTarget.blur()}
                    className={INPUT_CLASS}
                  />
                  <span className="text-muted-foreground text-xs shrink-0">—</span>
                  <NumericFormat
                    value={yearRange[1]}
                    decimalScale={0}
                    allowNegative={false}
                    placeholder="Máximo"
                    onValueChange={({ floatValue }) => {
                      if (floatValue == null) return;
                      const clamped = Math.max(yearRange[0], Math.min(floatValue, metadata.ranges.year.max));
                      setYearRange([yearRange[0], clamped]);
                    }}
                    onBlur={() => commitYear(yearRange[0], yearRange[1])}
                    onKeyDown={(e) => e.key === "Enter" && e.currentTarget.blur()}
                    className={INPUT_CLASS}
                  />
                </div>
              </div>
            </AccordionContent>
          </AccordionItem>

          {/* ─── Sources ─────────────────────────────────────────── */}
          <AccordionItem value="sources" className="border-border/50">
            <AccordionTrigger className="text-sm font-medium py-3 hover:no-underline">
              <div className="flex">
                Portais
                {store.sources.length > 0 && (
                  <span className="ml-2 rounded-full bg-primary/20 px-2 py-0.5 text-[10px] font-semibold text-primary">
                    {store.sources.length}
                  </span>
                )}
              </div>
            </AccordionTrigger>
            <AccordionContent>
              <div className="space-y-2 pt-1">
                {metadata.sources.map((source) => (
                  <label key={source} className="flex items-center gap-2.5 cursor-pointer group">
                    <Checkbox
                      checked={store.sources.includes(source)}
                      onCheckedChange={(checked) => {
                        if (checked) {
                          store.setSources([...store.sources, source]);
                        } else {
                          store.setSources(store.sources.filter((s) => s !== source));
                        }
                      }}
                      className="data-[state=checked]:bg-primary data-[state=checked]:border-primary"
                    />
                    <span className="text-sm text-muted-foreground group-hover:text-foreground transition-colors capitalize">
                      {source}
                    </span>
                  </label>
                ))}
              </div>
            </AccordionContent>
          </AccordionItem>
        </Accordion>
      </ScrollArea>

      <Separator className="my-4 bg-border/50" />

      <Button
        variant="outline"
        size="sm"
        onClick={handleReset}
        className="w-full border-border/50 text-muted-foreground hover:text-foreground"
      >
        Limpar todos os filtros
      </Button>
    </div>
  );
}
