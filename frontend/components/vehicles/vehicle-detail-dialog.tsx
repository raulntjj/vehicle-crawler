"use client";

import { useState, useEffect } from "react";
import { ExternalLink, ZoomIn, ZoomOut, X } from "lucide-react";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogPortal, DialogFooter } from "@/components/ui/dialog";
import { Separator } from "@/components/ui/separator";
import { Skeleton } from "@/components/ui/skeleton";
import { Button } from "@/components/ui/button";
import { useVehicleDetail } from "@/hooks/useVehicleDetail";
import type { PriceHistoryEntry } from "@/lib/types";

// Componentes do Carrossel do Shadcn UI
import {
  Carousel,
  CarouselContent,
  CarouselItem,
  CarouselNext,
  CarouselPrevious,
  type CarouselApi,
} from "@/components/ui/carousel";

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
        <span className="text-xs sm:text-sm font-medium text-muted-foreground">Variação:</span>
        <span className={`text-xs sm:text-sm font-semibold ${isDown ? "text-green-400" : diff > 0 ? "text-red-400" : "text-muted-foreground"}`}>
          {isDown ? "↓" : diff > 0 ? "↑" : "="}{" "}
          {new Intl.NumberFormat("pt-BR", { style: "currency", currency: "BRL", maximumFractionDigits: 0 }).format(Math.abs(diff))}
        </span>
      </div>
      <div className="w-full overflow-hidden rounded-lg border border-border/10 bg-muted/10 p-2">
        <svg viewBox={`0 0 ${w} ${h}`} className="w-full h-20 sm:h-24" preserveAspectRatio="none">
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
      </div>
      <div className="space-y-1.5 max-h-36 overflow-y-auto pr-1 scrollbar-thin">
        {[...history].reverse().map((entry, i) => (
          <div key={i} className="flex items-center justify-between text-xs sm:text-sm py-0.5 border-b border-border/5 last:border-0">
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
  );
}

export function VehicleDetailDialog({ vehicleId, onClose }: VehicleDetailDialogProps) {
  const { data: vehicle, isLoading } = useVehicleDetail(vehicleId);
  
  const [api, setApi] = useState<CarouselApi>();
  const [current, setCurrent] = useState(0);
  const [zoomImage, setZoomImage] = useState<string | null>(null);
  const [isZoomed, setIsZoomed] = useState(false);

  // Derivação de estado para evitar useEffects síncronos
  const images = (vehicle?.images || []).filter(Boolean) as string[];

  const [prevVehicleId, setPrevVehicleId] = useState(vehicleId);
  if (vehicleId !== prevVehicleId) {
    setPrevVehicleId(vehicleId);
    setCurrent(0);
  }

  const [prevZoomImage, setPrevZoomImage] = useState(zoomImage);
  if (zoomImage !== prevZoomImage) {
    setPrevZoomImage(zoomImage);
    setIsZoomed(false);
  }

  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Escape" && zoomImage) {
        e.stopPropagation();
        setZoomImage(null);
      }
    };
    window.addEventListener("keydown", handleKeyDown, true);
    return () => window.removeEventListener("keydown", handleKeyDown, true);
  }, [zoomImage]);

  useEffect(() => {
    if (!api) return;
    api.on("select", () => {
      setCurrent(api.selectedScrollSnap());
    });
  }, [api]);

  return (
    <>
      <Dialog open={vehicleId !== null} onOpenChange={(open) => !open && onClose()}>
      <DialogContent 
        className="max-w-[95vw] sm:max-w-[640px] bg-card border-border/50 max-h-[85vh] sm:max-h-[90vh] flex flex-col p-0 overflow-hidden"
        onPointerDownOutside={(e) => e.preventDefault()}
        onEscapeKeyDown={(e) => e.preventDefault()}
      >
        {isLoading ? (
          <div className="p-4 sm:p-6"><DialogSkeleton /></div>
        ) : vehicle ? (
          <>
            <DialogHeader className="p-4 sm:p-6 pb-2 sm:pb-2 pr-10 shrink-0">
              <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                  <p className="text-[10px] sm:text-xs font-semibold text-primary uppercase tracking-wider mb-1">{vehicle.brand}</p>
                  <DialogTitle className="text-base sm:text-lg font-bold leading-snug text-foreground break-words">{vehicle.title}</DialogTitle>
                </div>
              </div>
            </DialogHeader>

            <div className="flex-1 overflow-y-auto px-4 sm:px-6 pb-6 space-y-4 sm:space-y-5 scrollbar-thin">
              
              {/* Carrossel Shadcn utilizando tags img normais */}
              {images.length > 0 && (
                <div className="space-y-2 w-full min-w-0 group/gallery">
                  <Carousel setApi={setApi} className="w-full">
                    <CarouselContent>
                      {images.map((img, idx) => (
                        <CarouselItem key={idx}>
                          <div 
                            onClick={() => setZoomImage(img)}
                            className="relative h-[185px] sm:h-[260px] w-full overflow-hidden rounded-lg bg-black/90 border border-border/10 select-none cursor-zoom-in group/zoom-container"
                          >
                            <img
                              src={img}
                              alt={`${vehicle.title} - Foto ${idx + 1}`}
                              className="h-full w-full object-contain transition-transform duration-300 group-hover/zoom-container:scale-[1.02]"
                              loading={idx === 0 ? "eager" : "lazy"}
                            />
                            <div className="absolute bottom-2 right-2 p-1.5 rounded-md bg-black/60 text-white opacity-0 group-hover/zoom-container:opacity-100 transition-opacity pointer-events-none">
                              <ZoomIn className="h-4 w-4" />
                            </div>
                          </div>
                        </CarouselItem>
                      ))}
                    </CarouselContent>

                    {images.length > 1 && (
                      <>
                        <CarouselPrevious className="absolute left-2.5 top-1/2 -translate-y-1/2 h-8 w-8 bg-black/50 hover:bg-black/80 text-white border-white/10 opacity-80 sm:opacity-0 sm:group-hover/gallery:opacity-100 transition-opacity" />
                        <CarouselNext className="absolute right-2.5 top-1/2 -translate-y-1/2 h-8 w-8 bg-black/50 hover:bg-black/80 text-white border-white/10 opacity-80 sm:opacity-0 sm:group-hover/gallery:opacity-100 transition-opacity" />
                        
                        <div className="absolute top-2.5 right-2.5 px-2 py-0.5 rounded bg-black/60 text-[9px] font-bold border border-white/10 text-white backdrop-blur-xs z-10 uppercase tracking-widest pointer-events-none">
                          {current + 1} / {images.length}
                        </div>
                      </>
                    )}
                  </Carousel>

                  {/* Miniaturas */}
                  {images.length > 1 && (
                    <div className="flex gap-2 overflow-x-auto py-1 w-full max-w-full scrollbar-thin scrollbar-thumb-border/50 scrollbar-track-transparent">
                      {images.map((img, idx) => (
                        <button
                          key={idx}
                          type="button"
                          onClick={() => api?.scrollTo(idx)}
                          className={`relative aspect-[4/3] w-14 sm:w-16 shrink-0 overflow-hidden rounded-md border-2 transition-all ${
                            idx === current
                              ? "border-primary shadow-sm scale-102 ring-1 ring-primary/30"
                              : "border-border/30 hover:border-border/80 opacity-80 hover:opacity-100"
                          }`}
                        >
                          <img 
                            src={img} 
                            alt="" 
                            className="h-full w-full object-cover" 
                          />
                        </button>
                      ))}
                    </div>
                  )}
                </div>
              )}

              <p className="text-xl sm:text-2xl font-bold text-foreground">{vehicle.price_formatted}</p>

              {/* Grid de Especificações */}
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-2 sm:gap-3">
                <div className="rounded-lg bg-muted/20 border border-border/5 p-2.5 sm:p-3 min-w-0">
                  <p className="text-[9px] sm:text-[10px] uppercase tracking-wider text-muted-foreground mb-0.5">Ano</p>
                  <p className="text-xs sm:text-sm font-semibold truncate">{vehicle.year_formatted}</p>
                </div>
                <div className="rounded-lg bg-muted/20 border border-border/5 p-2.5 sm:p-3 min-w-0">
                  <p className="text-[9px] sm:text-[10px] uppercase tracking-wider text-muted-foreground mb-0.5">Quilometragem</p>
                  <p className="text-xs sm:text-sm font-semibold truncate">{vehicle.km_formatted}</p>
                </div>
                <div className="rounded-lg bg-muted/20 border border-border/5 p-2.5 sm:p-3 min-w-0">
                  <p className="text-[9px] sm:text-[10px] uppercase tracking-wider text-muted-foreground mb-0.5">Câmbio</p>
                  <p className="text-xs sm:text-sm font-semibold capitalize truncate">{vehicle.transmission || "Não informado"}</p>
                </div>
                <div className="rounded-lg bg-muted/20 border border-border/5 p-2.5 sm:p-3 min-w-0">
                  <p className="text-[9px] sm:text-[10px] uppercase tracking-wider text-muted-foreground mb-0.5">Carroceria</p>
                  <p className="text-xs sm:text-sm font-semibold capitalize truncate">{vehicle.bodystyle || "Não informado"}</p>
                </div>
                <div className="rounded-lg bg-muted/20 border border-border/5 p-2.5 sm:p-3 min-w-0">
                  <p className="text-[9px] sm:text-[10px] uppercase tracking-wider text-muted-foreground mb-0.5">Combustível</p>
                  <p className="text-xs sm:text-sm font-semibold capitalize truncate">{vehicle.fuel || "Não informado"}</p>
                </div>
                <div className="rounded-lg bg-muted/20 border border-border/5 p-2.5 sm:p-3 min-w-0">
                  <p className="text-[9px] sm:text-[10px] uppercase tracking-wider text-muted-foreground mb-0.5">Portas</p>
                  <p className="text-xs sm:text-sm font-semibold truncate">
                    {vehicle.doors ? `${vehicle.doors} Portas` : "Não informado"}
                  </p>
                </div>
                <div className="rounded-lg bg-muted/20 border border-border/5 p-2.5 sm:p-3 col-span-2 min-w-0">
                  <p className="text-[9px] sm:text-[10px] uppercase tracking-wider text-muted-foreground mb-0.5">Modelo</p>
                  <p className="text-xs sm:text-sm font-semibold truncate">{vehicle.model}</p>
                </div>
                <div className="rounded-lg bg-muted/20 border border-border/5 p-2.5 sm:p-3 col-span-1 min-w-0">
                  <p className="text-[9px] sm:text-[10px] uppercase tracking-wider text-muted-foreground mb-0.5">Portal</p>
                  <p className="text-xs sm:text-sm font-semibold capitalize truncate">{vehicle.source}</p>
                </div>
              </div>

              <Separator className="bg-border/50" />

              <div>
                <h4 className="text-xs sm:text-sm font-semibold mb-3">Histórico de Preços</h4>
                <PriceChart history={vehicle.price_history || []} />
              </div>

            </div>

            <DialogFooter className="p-3 border-t border-border/50 flex justify-between bg-card">
              <Button 
                variant="outline" 
                onClick={onClose} 
                className="cursor-pointer"
                size="sm"
              >
                Fechar
              </Button>
              <Button asChild className="cursor-pointer" size="sm">
                <a href={vehicle.url} target="_blank" rel="noopener noreferrer">
                  Ver anúncio original
                  <ExternalLink className="ml-1.5 h-3.5 w-3.5 shrink-0" />
                </a>
              </Button>
            </DialogFooter>
          </>
        ) : null}
      </DialogContent>

      {/* Lightbox / Zoom da Imagem */}
      {zoomImage && (
        <DialogPortal>
          <div 
            className="fixed inset-0 z-[150] bg-black/95 flex flex-col justify-center items-center animate-fade-in pointer-events-auto"
            onClick={() => setZoomImage(null)}
          >
            {/* Botões do Lightbox */}
            <div className="absolute top-4 right-4 z-[160] flex gap-2" onClick={(e) => e.stopPropagation()}>
              <button
                type="button"
                onClick={() => setIsZoomed(!isZoomed)}
                className="rounded-full bg-white/10 hover:bg-white/20 p-2.5 text-white transition-all flex items-center justify-center border border-white/10 active:scale-95 cursor-pointer"
                title={isZoomed ? "Reduzir" : "Ampliar"}
              >
                {isZoomed ? <ZoomOut className="h-5 w-5" /> : <ZoomIn className="h-5 w-5" />}
              </button>
              <button
                type="button"
                onClick={() => setZoomImage(null)}
                className="rounded-full bg-white/10 hover:bg-white/20 p-2.5 text-white transition-all flex items-center justify-center border border-white/10 active:scale-95 cursor-pointer"
                title="Fechar"
              >
                <X className="h-5 w-5" />
              </button>
            </div>
            
            {/* Imagem Ampliada */}
            <div className="w-full h-full flex items-center justify-center p-4 overflow-auto scrollbar-none">
              <img
                src={zoomImage}
                alt="Visualização ampliada do veículo"
                className={`object-contain rounded-md select-none transition-all duration-300 max-w-[90vw] max-h-[85vh] ${
                  isZoomed 
                    ? "scale-150 cursor-zoom-out shadow-2xl shadow-black/80" 
                    : "scale-100 cursor-zoom-in hover:brightness-105"
                }`}
                onClick={(e) => {
                  e.stopPropagation();
                  setIsZoomed(!isZoomed);
                }}
              />
            </div>
          </div>
        </DialogPortal>
      )}
      </Dialog>
    </>
  );
}