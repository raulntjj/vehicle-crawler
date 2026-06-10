"use client";

import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import type { Vehicle } from "@/lib/types";

interface VehicleCardProps {
  vehicle: Vehicle;
  onClick: () => void;
  index: number;
}

export function VehicleCard({ vehicle, onClick, index }: VehicleCardProps) {
  return (
    <Card
      onClick={onClick}
      className="group cursor-pointer overflow-hidden border-border/50 bg-card hover:border-primary/40 transition-all duration-300 hover:shadow-lg hover:shadow-primary/5 animate-fade-in-up flex flex-col h-full"
      style={{ animationDelay: `${index * 50}ms` }}
    >
      {vehicle.image ? (
        <div className="relative aspect-[16/10] w-full overflow-hidden bg-muted border-b border-border/10">
          <img
            src={vehicle.image}
            alt={vehicle.title}
            className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
            loading="lazy"
          />
        </div>
      ) : (
        <div className="relative aspect-[16/10] w-full overflow-hidden bg-muted/20 border-b border-border/10 flex items-center justify-center text-muted-foreground/30">
          <svg className="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
      )}
      <CardContent className="p-4 flex-1 flex flex-col justify-between">
        <div>
          <div className="flex items-start justify-between gap-2 mb-2">
            <div className="min-w-0 flex-1">
              <p className="text-[10px] font-semibold text-primary uppercase tracking-wider mb-0.5">{vehicle.brand}</p>
              <h3 className="text-sm font-semibold text-foreground leading-snug line-clamp-2 group-hover:text-primary/95 transition-colors">{vehicle.title}</h3>
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
