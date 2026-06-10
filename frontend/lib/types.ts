// ─── Vehicle ────────────────────────────────────────────────────────────────

export interface Vehicle {
  id: number;
  external_id: string;
  brand: string;
  model: string;
  title: string;
  price: number;
  price_formatted: string;
  km: number;
  km_formatted: string;
  year_fabrication: number;
  year_model: number;
  year_formatted: string;
  url: string;
  source: string;
  price_history: PriceHistoryEntry[] | null;
  created_at: string;
  updated_at: string;
}

export interface PriceHistoryEntry {
  price: number;
  price_formatted: string;
  date: string;
  date_formatted: string;
}

export type VehicleDetail = Vehicle & {
  price_history: PriceHistoryEntry[];
};

// ─── Filter Metadata ────────────────────────────────────────────────────────

export interface RangeMinMax {
  min: number;
  max: number;
}

export interface FilterMetadata {
  brands: string[];
  sources: string[];
  ranges: {
    price: RangeMinMax;
    km: RangeMinMax;
    year: RangeMinMax;
  };
}

// ─── Pagination ─────────────────────────────────────────────────────────────

export interface PaginationMeta {
  current_page: number;
  from: number | null;
  last_page: number;
  per_page: number;
  to: number | null;
  total: number;
  path: string;
}

export interface PaginationLinks {
  first: string | null;
  last: string | null;
  prev: string | null;
  next: string | null;
}

// ─── API Response Envelopes ─────────────────────────────────────────────────

export interface ApiResponse<T> {
  data: T;
  meta?: PaginationMeta;
  links?: PaginationLinks;
}

export interface ApiError {
  success: false;
  message: string;
  errors: Record<string, string[]>;
}
