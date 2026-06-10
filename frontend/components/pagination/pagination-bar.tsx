"use client";

import { Button } from "@/components/ui/button";

interface PaginationBarProps {
  currentPage: number;
  lastPage: number;
  onPageChange: (page: number) => void;
}

export function PaginationBar({ currentPage, lastPage, onPageChange }: PaginationBarProps) {
  if (lastPage <= 1) return null;

  // Build page numbers to show
  const pages: (number | "ellipsis")[] = [];
  const delta = 2;

  for (let i = 1; i <= lastPage; i++) {
    if (
      i === 1 ||
      i === lastPage ||
      (i >= currentPage - delta && i <= currentPage + delta)
    ) {
      pages.push(i);
    } else if (pages[pages.length - 1] !== "ellipsis") {
      pages.push("ellipsis");
    }
  }

  return (
    <nav className="flex items-center justify-center gap-1.5 pt-8 pb-4" aria-label="Paginação">
      <Button
        variant="outline"
        size="sm"
        disabled={currentPage <= 1}
        onClick={() => onPageChange(currentPage - 1)}
        className="border-border/50 text-muted-foreground hover:text-foreground disabled:opacity-30"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="m15 18-6-6 6-6" />
        </svg>
        <span className="hidden sm:inline ml-1">Anterior</span>
      </Button>

      {pages.map((page, i) =>
        page === "ellipsis" ? (
          <span key={`e-${i}`} className="px-2 text-muted-foreground/50 text-sm">
            …
          </span>
        ) : (
          <Button
            key={page}
            variant={page === currentPage ? "default" : "outline"}
            size="sm"
            onClick={() => onPageChange(page)}
            className={
              page === currentPage
                ? "bg-primary text-primary-foreground hover:bg-primary/90"
                : "border-border/50 text-muted-foreground hover:text-foreground"
            }
          >
            {page}
          </Button>
        )
      )}

      <Button
        variant="outline"
        size="sm"
        disabled={currentPage >= lastPage}
        onClick={() => onPageChange(currentPage + 1)}
        className="border-border/50 text-muted-foreground hover:text-foreground disabled:opacity-30"
      >
        <span className="hidden sm:inline mr-1">Próxima</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="m9 18 6-6-6-6" />
        </svg>
      </Button>
    </nav>
  );
}
