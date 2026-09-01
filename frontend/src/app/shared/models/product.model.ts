export interface Product {
  id: number;
  external_code: string;
  name: string;
  description: string | null;
  price: number;
  purchase_price: number | null;
  discount: number | null;
  created_at: string;
  updated_at: string;
  attributes: ProductAttribute[];
  images: ProductImage[];
}

export interface ProductListItem {
  id: number;
  external_code: string;
  name: string;
  price: number;
  discount: number | null;
  images_count: number;
}

export interface ProductFilter {
  name?: string;
  minPrice?: number;
  maxPrice?: number;
}

export interface PaginatedResponse<T> {
  items: T[];
  total: number;
  page: number;
  limit: number;
  total_pages: number;
}

export interface ProductAttribute {
  id: number;
  key: string;
  value: string | null;
}

export interface ProductImage {
  id: number;
  url: string;
  path: string | null;
}
