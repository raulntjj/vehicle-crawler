"use client";

import { useState, useEffect } from "react";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Skeleton } from "@/components/ui/skeleton";
import { Button } from "@/components/ui/button";
import { useVehicleDetail } from "@/hooks/useVehicleDetail";
import type { PriceHistoryEntry } from "@/lib/types";

interface VehicleDetailDialogProps {
  vehicleId: number | null;
  onClose: () => void;
}

function PriceChart({ history }: { history: PriceHistoryEntry[] }) {
  if (history.length < 2) {
    return (
      <div className="text-sm text-muted-foreground text-center py-4">
        Sem variações de preço registradas.
      </div>
    );
  }

  const prices = history.map((h) => h.price);
  const minP = Math.min(...prices);
  const maxP = Math.max(...prices);
  const range = maxP - minP || 1;

  const w = 100;
  const h = 40;
  const padY = 4;

  const points = history.map((entry, i) => {
    const x = (i / (history.length - 1)) * w;
    const y = h - padY - ((entry.price - minP) / range) * (h - padY * 2);
    return `${x},${y}`;
  });

  const lastPrice = history[history.length - 1];
  const firstPrice = history[0];
  const diff = lastPrice.price - firstPrice.price;
  const isDown = diff < 0;

  return (
    <div className="space-y-3">
      <div className="flex items-center gap-2">
        <span className="text-sm font-medium text-muted-foreground">Variação:</span>
        <span className={`text-sm font-semibold ${isDown ? "text-green-400" : diff > 0 ? "text-red-400" : "text-muted-foreground"}`}>
          {isDown ? "↓" : diff > 0 ? "↑" : "="}{" "}
          {new Intl.NumberFormat("pt-BR", { style: "currency", currency: "BRL", maximumFractionDigits: 0 }).format(Math.abs(diff))}
        </span>
      </div>
      <svg viewBox={`0 0 ${w} ${h}`} className="w-full h-24" preserveAspectRatio="none">
        <defs>
          <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stopColor={isDown ? "rgb(74, 222, 128)" : "rgb(248, 113, 113)"} stopOpacity="0.3" />
            <stop offset="100%" stopColor={isDown ? "rgb(74, 222, 128)" : "rgb(248, 113, 113)"} stopOpacity="0" />
          </linearGradient>
        </defs>
        <polygon
          points={`0,${h} ${points.join(" ")} ${w},${h}`}
          fill="url(#chartGrad)"
        />
        <polyline
          points={points.join(" ")}
          fill="none"
          stroke={isDown ? "rgb(74, 222, 128)" : "rgb(248, 113, 113)"}
          strokeWidth="1.5"
          strokeLinecap="round"
          strokeLinejoin="round"
          vectorEffect="non-scaling-stroke"
        />
      </svg>
      <div className="space-y-1.5">
        {[...history].reverse().map((entry, i) => (
          <div key={i} className="flex items-center justify-between text-sm">
            <span className="text-muted-foreground">{entry.date_formatted}</span>
            <span className="font-medium text-foreground">{entry.price_formatted}</span>
          </div>
        ))}
      </div>
    </div>
  );
}

function DialogSkeleton() {
  return (
    <div className="space-y-4 py-4">
      <Skeleton className="h-6 w-3/4" />
      <Skeleton className="h-8 w-40" />
      <div className="grid grid-cols-2 gap-4">
        <Skeleton className="h-16" />
        <Skeleton className="h-16" />
      </div>
      <Skeleton className="h-32 w-full" />
    </div>
  )
}

export function VehicleDetailDialog({ vehicleId, onClose }: VehicleDetailDialogProps) {
  const { data: vehicle, isLoading } = useVehicleDetail(vehicleId);
  const [activeImageIndex, setActiveImageIndex] = useState(0);

  useEffect(() => {
    setActiveImageIndex(0);
  }, [vehicleId]);

  return (
    <Dialog open={vehicleId !== null} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="sm:max-w-[560px] bg-card border-border/50 max-h-[90vh] overflow-y-auto">
        {isLoading ? <DialogSkeleton /> : vehicle ? (
          <>
            <DialogHeader>
              <div className="flex items-start justify-between gap-3">
                <div>
                  <p className="text-xs font-medium text-primary uppercase tracking-wider mb-1">{vehicle.brand}</p>
                  <DialogTitle className="text-lg font-bold leading-snug">{vehicle.title}</DialogTitle>
                </div>
              </div>
            </DialogHeader>

            <div className="space-y-5 mt-2">
              {(() => {
                const images = (vehicle.images || (vehicle.image ? [vehicle.image] : [])).filter(Boolean) as string[];
                if (images.length === 0) return null;
                const activeImage = images[activeImageIndex];

                return (
                  <div className="space-y-2">
                    <div className="relative aspect-video w-full overflow-hidden rounded-lg bg-muted border border-border/10 group">
                      <img
                        src={activeImage}
                        alt={vehicle.title}
                        className="h-full w-full object-cover"
                      />
                      {images.length > 1 && (
                        <>
                          <button
                            type="button"
                            onClick={() => setActiveImageIndex((prev) => (prev === 0 ? images.length - 1 : prev - 1))}
                            className="absolute left-2 top-1/2 -translate-y-1/2 h-8 w-8 rounded-full bg-background/80 hover:bg-background border border-border/20 flex items-center justify-center text-foreground shadow-md transition-colors opacity-0 group-hover:opacity-100 focus:opacity-100"
                          >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" />
                            </svg>
                          </button>
                          <button
                            type="button"
                            onClick={() => setActiveImageIndex((prev) => (prev === images.length - 1 ? 0 : prev + 1))}
                            className="absolute right-2 top-1/2 -translate-y-1/2 h-8 w-8 rounded-full bg-background/80 hover:bg-background border border-border/20 flex items-center justify-center text-foreground shadow-md transition-colors opacity-0 group-hover:opacity-100 focus:opacity-100"
                          >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
                            </svg>
                          </button>
                          <div className="absolute bottom-2 right-2 px-2 py-0.5 rounded bg-background/80 text-[10px] font-semibold border border-border/10">
                            {activeImageIndex + 1} / {images.length}
                          </div>
                        </>
                      )}
                    </div>
                    {images.length > 1 && (
                      <div className="flex gap-2 overflow-x-auto pb-1 scrollbar-thin scrollbar-thumb-border/50 scrollbar-track-transparent">
                        {images.map((img, idx) => (
                          <button
                            key={idx}
                            type="button"
                            onClick={() => setActiveImageIndex(idx)}
                            className={`relative aspect-[4/3] w-16 shrink-0 overflow-hidden rounded border-2 transition-all ${
                              idx === activeImageIndex
                                ? "border-primary scale-95"
                                : "border-border/30 hover:border-border/70"
                            }`}
                          >
                            <img src={img} alt="" className="h-full w-full object-cover" />
                          </button>
                        ))}
                      </div>
                    )}
                  </div>
                );
              })()}

              <p className="text-2xl font-bold text-foreground">{vehicle.price_formatted}</p>

              <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <div className="rounded-lg bg-muted/30 p-3">
                  <p className="text-[10px] uppercase tracking-wider text-muted-foreground mb-0.5">Ano</p>
                  <p className="text-sm font-semibold">{vehicle.year_formatted}</p>
                </div>
                <div className="rounded-lg bg-muted/30 p-3">
                  <p className="text-[10px] uppercase tracking-wider text-muted-foreground mb-0.5">Quilometragem</p>
                  <p className="text-sm font-semibold">{vehicle.km_formatted}</p>
                </div>
                <div className="rounded-lg bg-muted/30 p-3">
                  <p className="text-[10px] uppercase tracking-wider text-muted-foreground mb-0.5">Câmbio</p>
                  <p className="text-sm font-semibold capitalize">{vehicle.transmission || "Não informado"}</p>
                </div>
                <div className="rounded-lg bg-muted/30 p-3">
                  <p className="text-[10px] uppercase tracking-wider text-muted-foreground mb-0.5">Carroceria</p>
                  <p className="text-sm font-semibold capitalize">{vehicle.bodystyle || "Não informado"}</p>
                </div>
                <div className="rounded-lg bg-muted/30 p-3">
                  <p className="text-[10px] uppercase tracking-wider text-muted-foreground mb-0.5">Combustível</p>
                  <p className="text-sm font-semibold capitalize">{vehicle.fuel || "Não informado"}</p>
                </div>
                <div className="rounded-lg bg-muted/30 p-3">
                  <p className="text-[10px] uppercase tracking-wider text-muted-foreground mb-0.5">Portas</p>
                  <p className="text-sm font-semibold">{vehicle.doors ? `${vehicle.doors} Portas` : "Não informado"}</p>
                </div>
                <div className="rounded-lg bg-muted/30 p-3 col-span-2">
                  <p className="text-[10px] uppercase tracking-wider text-muted-foreground mb-0.5">Modelo</p>
                  <p className="text-sm font-semibold truncate">{vehicle.model}</p>
                </div>
                <div className="rounded-lg bg-muted/30 p-3 col-span-1">
                  <p className="text-[10px] uppercase tracking-wider text-muted-foreground mb-0.5">Portal</p>
                  <p className="text-sm font-semibold capitalize">{vehicle.source}</p>
                </div>
              </div>

              <Separator className="bg-border/50" />

              <div>
                <h4 className="text-sm font-semibold mb-3">Histórico de Preços</h4>
                <PriceChart history={vehicle.price_history || []} />
              </div>

              <Separator className="bg-border/50" />

              <Button asChild className="w-full" size="lg">
                <a href={vehicle.url} target="_blank" rel="noopener noreferrer">
                  Ver anúncio original
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="ml-2">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                    <polyline points="15 3 21 3 21 9" />
                    <line x1="10" x2="21" y1="14" y2="3" />
                  </svg>
                </a>
              </Button>
            </div>
          </>
        ) : null}
      </DialogContent>
    </Dialog>
  );
}
