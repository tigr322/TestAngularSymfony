import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { Component } from '@angular/core';
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
  selectedFile: File | null = null;
  result: ImportResponse | null = null;
  error: string | null = null;
  isLoading = false;

  constructor(private readonly productApi: ProductApiService) {}

  onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.selectedFile = input.files?.item(0) ?? null;
    this.result = null;
    this.error = null;
  }

  submit(): void {
    if (this.selectedFile === null) {
      this.error = 'Choose an .xlsx file before importing.';
      return;
    }

    this.isLoading = true;
    this.error = null;
    this.result = null;

    this.productApi.importProducts(this.selectedFile).subscribe({
      next: (response) => {
        this.result = response;
        this.isLoading = false;
      },
      error: (error: unknown) => {
        this.error = this.errorMessage(error);
        this.isLoading = false;
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
