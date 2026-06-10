"use client";

import { useState } from "react";
import { SlidersHorizontal } from "lucide-react";
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from "@/components/ui/sheet";
import { Button } from "@/components/ui/button";
import { FilterSidebar } from "./filter-sidebar";

export function FilterSheet() {
  const [open, setOpen] = useState(false);

  return (
    <Sheet open={open} onOpenChange={setOpen}>
      <SheetTrigger asChild>
        <Button variant="outline" size="sm" className="lg:hidden border-border/50 gap-2">
          <SlidersHorizontal className="h-3.5 w-3.5" />
          Filtros
        </Button>
      </SheetTrigger>
      <SheetContent side="left" className="w-[320px] sm:w-[380px] bg-background border-border/50 p-6">
        <SheetHeader>
          <SheetTitle className="text-left">Filtros</SheetTitle>
        </SheetHeader>
        <div className="mt-4 h-[calc(100vh-8rem)]">
          <FilterSidebar />
        </div>
      </SheetContent>
    </Sheet>
  );
}
