export interface ImportRowError {
  row: number;
  message: string;
}

export interface ImportResponse {
  created: number;
  updated: number;
  skipped: number;
  errorCount: number;
  errors: ImportRowError[];
}

export interface ProductAttribute {
  key: string;
  value: string;
}

export interface ProductImage {
  id: number;
  sourceUrl: string;
  localPath: string;
}

export interface ProductListItem {
  id: number;
  externalCode: string;
  name: string;
  price: string;
  purchasePrice: string | null;
  discountPercent: string | null;
  imageCount: number;
  firstImage: string | null;
}

export interface ProductListResponse {
  items: ProductListItem[];
}

export interface ProductDetails {
  id: number;
  externalCode: string;
  name: string;
  description: string | null;
  price: string;
  purchasePrice: string | null;
  discountPercent: string | null;
  createdAt: string;
  updatedAt: string;
  attributes: ProductAttribute[];
  images: ProductImage[];
}
