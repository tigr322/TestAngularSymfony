import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { Component, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';

import { ProductListItem } from '../../core/api/api.models';
import { ProductApiService } from '../../core/api/product-api.service';

@Component({
  selector: 'app-products-list-page',
  imports: [CommonModule, RouterLink],
  templateUrl: './products-list.page.html',
  styleUrl: './products-list.page.scss',
})
export class ProductsListPage implements OnInit {
  readonly products = signal<ProductListItem[]>([]);
  readonly isLoading = signal(true);
  readonly error = signal<string | null>(null);

  constructor(readonly productApi: ProductApiService) {}

  ngOnInit(): void {
    this.productApi.getProducts().subscribe({
      next: (response) => {
        this.products.set(response.items);
        this.isLoading.set(false);
      },
      error: (error: unknown) => {
        this.error.set(this.errorMessage(error));
        this.isLoading.set(false);
      },
    });
  }

  private errorMessage(error: unknown): string {
    if (error instanceof HttpErrorResponse && typeof error.error?.error?.message === 'string') {
      return error.error.error.message;
    }

    return 'Could not load products.';
  }
}
