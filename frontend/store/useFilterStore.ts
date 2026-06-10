import { create } from "zustand";

export interface FilterState {
  // Text search
  search: string;

  // Multi-select filters
  brands: string[];
  sources: string[];

  // Model keyword
  model: string;

  // Range filters (undefined = use API defaults)
  minPrice?: number;
  maxPrice?: number;
  minKm?: number;
  maxKm?: number;
  minYear?: number;
  maxYear?: number;

  // Sorting
  orderBy: string;
  orderDirection: "asc" | "desc";

  // Pagination
  perPage: number;
  page: number;
}

export interface FilterActions {
  setSearch: (search: string) => void;
  setBrands: (brands: string[]) => void;
  toggleBrand: (brand: string) => void;
  setSources: (sources: string[]) => void;
  setModel: (model: string) => void;
  setPriceRange: (min?: number, max?: number) => void;
  setKmRange: (min?: number, max?: number) => void;
  setYearRange: (min?: number, max?: number) => void;
  setOrderBy: (orderBy: string) => void;
  setOrderDirection: (direction: "asc" | "desc") => void;
  setPerPage: (perPage: number) => void;
  setPage: (page: number) => void;
  resetFilters: () => void;
}

const initialState: FilterState = {
  search: "",
  brands: [],
  sources: [],
  model: "",
  minPrice: undefined,
  maxPrice: undefined,
  minKm: undefined,
  maxKm: undefined,
  minYear: undefined,
  maxYear: undefined,
  orderBy: "created_at",
  orderDirection: "desc",
  perPage: 15,
  page: 1,
};

export const useFilterStore = create<FilterState & FilterActions>((set) => ({
  ...initialState,

  setSearch: (search) => set({ search, page: 1 }),

  setBrands: (brands) => set({ brands, page: 1 }),

  toggleBrand: (brand) =>
    set((state) => ({
      brands: state.brands.includes(brand)
        ? state.brands.filter((b) => b !== brand)
        : [...state.brands, brand],
      page: 1,
    })),

  setSources: (sources) => set({ sources, page: 1 }),

  setModel: (model) => set({ model, page: 1 }),

  setPriceRange: (min, max) => set({ minPrice: min, maxPrice: max, page: 1 }),

  setKmRange: (min, max) => set({ minKm: min, maxKm: max, page: 1 }),

  setYearRange: (min, max) => set({ minYear: min, maxYear: max, page: 1 }),

  setOrderBy: (orderBy) => set({ orderBy, page: 1 }),

  setOrderDirection: (orderDirection) => set({ orderDirection, page: 1 }),

  setPerPage: (perPage) => set({ perPage, page: 1 }),

  setPage: (page) => set({ page }),

  resetFilters: () => set(initialState),
}));
