import { Routes } from '@angular/router';

import { ImportProductsPage } from './features/import-products/import-products.page';
import { ProductDetailsPage } from './features/product-details/product-details.page';
import { ProductsListPage } from './features/products-list/products-list.page';

export const routes: Routes = [
  { path: '', pathMatch: 'full', redirectTo: 'import' },
  { path: 'import', component: ImportProductsPage },
  { path: 'products', component: ProductsListPage },
  { path: 'products/:id', component: ProductDetailsPage },
  { path: '**', redirectTo: 'import' },
];
