import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { Component, signal } from '@angular/core';
import { RouterLink } from '@angular/router';

import { ImportResponse } from '../../core/api/api.models';
import { ProductApiService } from '../../core/api/product-api.service';

@Component({
  selector: 'app-import-products-page',
  imports: [CommonModule, RouterLink],
  templateUrl: './import-products.page.html',
  styleUrl: './import-products.page.scss',
})
export class ImportProductsPage {
  readonly selectedFile = signal<File | null>(null);
  readonly result = signal<ImportResponse | null>(null);
  readonly error = signal<string | null>(null);
  readonly isLoading = signal(false);

  constructor(private readonly productApi: ProductApiService) {}

  onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.selectedFile.set(input.files?.item(0) ?? null);
    this.result.set(null);
    this.error.set(null);
  }

  submit(): void {
    const file = this.selectedFile();
    if (file === null) {
      this.error.set('Choose an .xlsx file before importing.');
      return;
    }

    this.isLoading.set(true);
    this.error.set(null);
    this.result.set(null);

    this.productApi.importProducts(file).subscribe({
      next: (response) => {
        this.result.set(response);
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

    return 'Import request failed.';
  }
}
