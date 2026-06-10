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
      className="group cursor-pointer overflow-hidden border-border/50 bg-card hover:border-primary/40 transition-all duration-300 hover:shadow-lg hover:shadow-primary/5 animate-fade-in-up"
      style={{ animationDelay: `${index * 50}ms` }}
    >
      {/* <div className="h-1.5 w-full bg-gradient-to-r from-primary/60 via-primary to-primary/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300" /> */}
      <CardContent className="p-5">
        <div className="flex items-start justify-between gap-2 mb-3">
          <div className="min-w-0 flex-1">
            <p className="text-xs font-medium text-primary uppercase tracking-wider mb-1">{vehicle.brand}</p>
            <h3 className="text-sm font-semibold text-foreground leading-snug line-clamp-2 group-hover:text-primary/90 transition-colors">{vehicle.title}</h3>
          </div>
          <Badge variant="secondary" className="shrink-0 text-[10px] uppercase tracking-wider bg-secondary/50 text-muted-foreground">{vehicle.source}</Badge>
        </div>
        <div className="mb-4">
          <p className="text-xl font-bold text-foreground tracking-tight">{vehicle.price_formatted}</p>
        </div>
        <div className="flex items-center gap-4 text-sm text-muted-foreground">
          <span>{vehicle.year_formatted}</span>
          <span className="text-border">•</span>
          <span>{vehicle.km_formatted}</span>
        </div>
      </CardContent>
    </Card>
  );
}
