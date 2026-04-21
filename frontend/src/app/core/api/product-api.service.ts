import { HttpClient } from '@angular/common/http';
import { Inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';

import { API_BASE_URL } from './api.config';
import { ImportResponse, ProductDetails, ProductListResponse } from './api.models';

@Injectable({ providedIn: 'root' })
export class ProductApiService {
  private readonly assetBaseUrl: string;

  constructor(
    private readonly http: HttpClient,
    @Inject(API_BASE_URL) private readonly apiBaseUrl: string,
  ) {
    this.assetBaseUrl = this.apiBaseUrl.replace(/\/api\/?$/, '');
  }

  importProducts(file: File): Observable<ImportResponse> {
    const formData = new FormData();
    formData.append('file', file);

    return this.http.post<ImportResponse>(`${this.apiBaseUrl}/import/products`, formData);
  }

  getProducts(): Observable<ProductListResponse> {
    return this.http.get<ProductListResponse>(`${this.apiBaseUrl}/products`);
  }

  getProduct(id: number): Observable<ProductDetails> {
    return this.http.get<ProductDetails>(`${this.apiBaseUrl}/products/${id}`);
  }

  imageUrl(path: string | null): string | null {
    if (path === null || path === '') {
      return null;
    }

    if (/^https?:\/\//.test(path)) {
      return path;
    }

    return `${this.assetBaseUrl}${path}`;
  }
}
