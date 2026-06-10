"use client";

import { useState, useEffect, useMemo } from "react";
import { ChevronLeft, ChevronRight, ImageOff } from "lucide-react";
import Autoplay from "embla-carousel-autoplay";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
  Carousel,
  CarouselContent,
  CarouselItem,
  type CarouselApi,
} from "@/components/ui/carousel";
import type { Vehicle } from "@/lib/types";

interface VehicleCardProps {
  vehicle: Vehicle;
  onClick: () => void;
  index: number;
}

export function VehicleCard({ vehicle, onClick, index }: VehicleCardProps) {
  const [api, setApi] = useState<CarouselApi>();
  const [currentImageIndex, setCurrentImageIndex] = useState(0);
  const [isHovered, setIsHovered] = useState(false);

  const plugins = useMemo(
    () => [
      Autoplay({
        delay: 1800,
        stopOnInteraction: false,
        active: false, // Começa desativado e controlamos no useEffect
      }),
    ],
    []
  );

  const images = (vehicle.images || []).filter(Boolean);
  const hasImages = images.length > 0;

  // Monitora a mudança de slide do Embla
  useEffect(() => {
    if (!api) return;
    api.on("select", () => {
      setCurrentImageIndex(api.selectedScrollSnap());
    });
  }, [api]);

  // Controla o Autoplay nativo baseado no Hover
  useEffect(() => {
    const plugin = api?.plugins().autoplay as { play: () => void; stop: () => void } | undefined;
    if (!plugin) return;

    try {
      if (isHovered) {
        plugin.play();
      } else {
        plugin.stop();
        api?.scrollTo(0); // Reseta para a primeira imagem ao sair
      }
    } catch (err) {
      console.warn("Autoplay interaction error:", err);
    }
  }, [isHovered, api]);

  const handlePrev = (e: React.MouseEvent) => {
    e.stopPropagation();
    api?.scrollPrev();
  };

  const handleNext = (e: React.MouseEvent) => {
    e.stopPropagation();
    api?.scrollNext();
  };

  const handleDotClick = (idx: number, e: React.MouseEvent) => {
    e.stopPropagation();
    api?.scrollTo(idx);
  };

  return (
    <Card
      onClick={onClick}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
      className="group/card cursor-pointer overflow-hidden border-border/50 bg-card hover:border-primary/40 transition-all duration-300 hover:shadow-lg hover:shadow-primary/5 animate-fade-in-up flex flex-col h-full p-0 gap-0"
      style={{ animationDelay: `${index * 50}ms` }}
    >
      {/* Container da Imagem com Carrossel */}
      <div className="relative aspect-[16/10] w-full overflow-hidden bg-muted border-b border-border/10 group/carousel">
        {hasImages ? (
          <>
            <Carousel 
              setApi={setApi} 
              opts={{ watchDrag: false, loop: true }}
              plugins={plugins}
              className="w-full h-full"
            >
              <CarouselContent className="-ml-0 h-full">
                {images.map((img, idx) => (
                  <CarouselItem key={idx} className="pl-0 h-full w-full">
                    <img
                      src={img}
                      alt={vehicle.title}
                      className="h-full w-full object-cover transition-all duration-500 group-hover/card:scale-105"
                      loading={idx === 0 ? "eager" : "lazy"}
                    />
                  </CarouselItem>
                ))}
              </CarouselContent>
            </Carousel>

            {/* Linhas indicadoras no estilo "Stories" (apenas no hover e se houver mais de 1 imagem) */}
            {images.length > 1 && isHovered && (
              <div className="absolute top-2 left-0 right-0 flex gap-1 px-2 z-10">
                {images.map((_, idx) => (
                  <div
                    key={idx}
                    className="h-1 flex-1 rounded-full overflow-hidden bg-black/40"
                  >
                    <div
                      className={`h-full bg-white transition-all duration-300 ${
                        idx <= currentImageIndex ? "w-full" : "w-0"
                      }`}
                    />
                  </div>
                ))}
              </div>
            )}

            {/* Setas Minimalistas (aparecem ao passar o mouse na imagem) */}
            {images.length > 1 && (
              <>
                <button
                  type="button"
                  onClick={handlePrev}
                  className="absolute left-2 top-1/2 -translate-y-1/2 h-7 w-7 rounded-full bg-black/50 hover:bg-black/75 flex items-center justify-center text-white backdrop-blur-sm transition-all opacity-0 group-hover/carousel:opacity-100 focus:opacity-100 z-10 border border-white/10"
                >
                  <ChevronLeft className="w-3.5 h-3.5" strokeWidth={2.5} />
                </button>
                <button
                  type="button"
                  onClick={handleNext}
                  className="absolute right-2 top-1/2 -translate-y-1/2 h-7 w-7 rounded-full bg-black/50 hover:bg-black/75 flex items-center justify-center text-white backdrop-blur-sm transition-all opacity-0 group-hover/carousel:opacity-100 focus:opacity-100 z-10 border border-white/10"
                >
                  <ChevronRight className="w-3.5 h-3.5" strokeWidth={2.5} />
                </button>
              </>
            )}

            {/* Dots de navegação na base (se existirem mais imagens, visíveis mesmo sem hover) */}
            {images.length > 1 && !isHovered && (
              <div className="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1 px-1.5 py-0.5 rounded-full bg-black/30 backdrop-blur-xs z-10">
                {images.slice(0, 8).map((_, idx) => (
                  <button
                    key={idx}
                    type="button"
                    onClick={(e) => handleDotClick(idx, e)}
                    className={`h-1.5 w-1.5 rounded-full transition-all ${
                      idx === currentImageIndex ? "bg-white w-3" : "bg-white/55 hover:bg-white/90"
                    }`}
                  />
                ))}
                {images.length > 8 && (
                  <span className="text-[7px] text-white/80 self-center leading-none font-bold">+</span>
                )}
              </div>
            )}
          </>
        ) : (
          <div className="h-full w-full flex items-center justify-center text-muted-foreground/30">
            <ImageOff className="w-10 h-10" strokeWidth={1.5} />
          </div>
        )}
      </div>

      {/* Conteúdo do Card */}
      <CardContent className="p-4 flex-1 flex flex-col justify-between">
        <div>
          <div className="flex items-start justify-between gap-2 mb-2">
            <div className="min-w-0 flex-1">
              <p className="text-[10px] font-semibold text-primary uppercase tracking-wider mb-0.5">{vehicle.brand}</p>
              <h3 className="text-sm font-semibold text-foreground leading-snug line-clamp-2 group-hover/card:text-primary/95 transition-colors">{vehicle.title}</h3>
            </div>
            <Badge variant="secondary" className="shrink-0 text-[9px] uppercase tracking-wider bg-secondary/50 text-muted-foreground h-auto py-0.5 px-1.5">{vehicle.source}</Badge>
          </div>
          <div className="mb-3">
            <p className="text-lg font-bold text-foreground tracking-tight">{vehicle.price_formatted}</p>
          </div>
        </div>
        <div className="flex items-center gap-3 text-xs text-muted-foreground border-t border-border/50 pt-3 mt-auto">
          <span>{vehicle.year_formatted}</span>
          <span className="text-border/60">•</span>
          <span>{vehicle.km_formatted}</span>
        </div>
      </CardContent>
    </Card>
  );
}