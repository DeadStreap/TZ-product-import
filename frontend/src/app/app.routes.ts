import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';

export const routes: Routes = [
  { path: '', redirectTo: 'products', pathMatch: 'full' },
  {
    path: 'login',
    loadComponent: () => import('./features/auth/login.component').then(m => m.LoginComponent),
  },
  {
    path: 'import',
    loadComponent: () => import('./features/import/import.component').then(m => m.ImportComponent),
    canActivate: [authGuard],
  },
  {
    path: 'products',
    loadComponent: () => import('./features/products/list/product-list.component').then(m => m.ProductListComponent),
    canActivate: [authGuard],
  },
  {
    path: 'products/:id',
    loadComponent: () => import('./features/products/card/product-card.component').then(m => m.ProductCardComponent),
    canActivate: [authGuard],
  },
  { path: '**', redirectTo: 'products' },
];
