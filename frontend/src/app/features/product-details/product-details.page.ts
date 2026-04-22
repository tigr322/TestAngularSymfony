import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { Component, OnInit, signal } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';

import { ProductDetails } from '../../core/api/api.models';
import { ProductApiService } from '../../core/api/product-api.service';

@Component({
  selector: 'app-product-details-page',
  imports: [CommonModule, RouterLink],
  templateUrl: './product-details.page.html',
  styleUrl: './product-details.page.scss',
})
export class ProductDetailsPage implements OnInit {
  readonly product = signal<ProductDetails | null>(null);
  readonly isLoading = signal(true);
  readonly error = signal<string | null>(null);

  constructor(
    readonly productApi: ProductApiService,
    private readonly route: ActivatedRoute,
  ) {}

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!Number.isInteger(id) || id <= 0) {
      this.error.set('Товар не найден.');
      this.isLoading.set(false);
      return;
    }

    this.productApi.getProduct(id).subscribe({
      next: (product) => {
        this.product.set(product);
        this.isLoading.set(false);
      },
      error: (error: unknown) => {
        this.error.set(this.errorMessage(error));
        this.isLoading.set(false);
      },
    });
  }

  private errorMessage(error: unknown): string {
    if (error instanceof HttpErrorResponse && error.status === 404) {
      return 'Товар не найден.';
    }

    if (error instanceof HttpErrorResponse && typeof error.error?.error?.message === 'string') {
      return error.error.error.message;
    }

    return 'Не удалось загрузить товар.';
  }
}
