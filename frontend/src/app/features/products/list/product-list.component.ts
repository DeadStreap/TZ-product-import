import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { Store } from '@ngrx/store';
import { Observable } from 'rxjs';
import { ProductListItem, ProductFilter } from '@app/shared/models/product.model';
import * as ProductsActions from '../store/products.actions';
import {
  selectAllProducts,
  selectProductsLoading,
  selectProductsPage,
  selectProductsTotalPages,
} from '../store/products.selectors';

@Component({
  selector: 'app-product-list',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  template: `
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Products</h1>
        <a routerLink="/import"
           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium
                  rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
          Import New
        </a>
      </div>

      <!-- Filters -->
      <div class="bg-white shadow rounded-lg p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input type="text" [ngModel]="filter.name" (ngModelChange)="filter.name = $event"
                   placeholder="Search by name..."
                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                          text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Min Price</label>
            <input type="number" [ngModel]="filter.minPrice" (ngModelChange)="filter.minPrice = $event"
                   placeholder="0"
                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                          text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Max Price</label>
            <input type="number" [ngModel]="filter.maxPrice" (ngModelChange)="filter.maxPrice = $event"
                   placeholder="99999"
                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                          text-sm" />
          </div>
          <div class="flex items-end gap-2">
            <button (click)="applyFilter()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700
                           text-sm font-medium transition-colors">
              Apply
            </button>
            <button (click)="clearFilter()"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300
                           text-sm font-medium transition-colors">
              Clear
            </button>
          </div>
        </div>
      </div>

      <!-- Loading -->
      @if (loading$ | async) {
        <div class="text-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p class="mt-4 text-gray-500">Loading products...</p>
        </div>
      } @else {
        <!-- Table -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discount</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Images</th>
                  <th class="px-6 py-3"></th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                @for (product of products$ | async; track product.id) {
                  <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">{{ product.id }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                      <div class="max-w-xs truncate">{{ product.name }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">
                      {{ product.price | number:'1.2-2' }}
                    </td>
                    <td class="px-6 py-4 text-sm whitespace-nowrap">
                      @if (product.discount !== null) {
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                          {{ product.discount | number:'1.1-1' }}%
                        </span>
                      } @else {
                        <span class="text-gray-400">-</span>
                      }
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                      {{ product.images_count }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm whitespace-nowrap">
                      <a [routerLink]="['/products', product.id]"
                         class="text-blue-600 hover:text-blue-900 font-medium">
                        View
                      </a>
                    </td>
                  </tr>
                } @empty {
                  <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                      </svg>
                      <p class="mt-4">No products found. Try importing some!</p>
                    </td>
                  </tr>
                }
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination -->
        @if ((totalPages$ | async) && (totalPages$ | async)! > 1) {
          <div class="flex items-center justify-between">
            <button (click)="prevPage()" [disabled]="(page$ | async) === 1"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium
                           text-gray-700 bg-white hover:bg-gray-50
                           disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
              Previous
            </button>
            <span class="text-sm text-gray-700">
              Page {{ page$ | async }} of {{ totalPages$ | async }}
            </span>
            <button (click)="nextPage()" [disabled]="(page$ | async) === (totalPages$ | async)"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium
                           text-gray-700 bg-white hover:bg-gray-50
                           disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
              Next
            </button>
          </div>
        }
      }
    </div>
  `,
})
export class ProductListComponent implements OnInit {
  private store = inject(Store);

  products$: Observable<ProductListItem[]> = this.store.select(selectAllProducts);
  loading$: Observable<boolean> = this.store.select(selectProductsLoading);
  page$: Observable<number> = this.store.select(selectProductsPage);
  totalPages$: Observable<number> = this.store.select(selectProductsTotalPages);

  filter: ProductFilter = {};
  private currentPage = 1;
  private readonly limit = 20;

  ngOnInit(): void {
    this.loadProducts();
  }

  loadProducts(): void {
    this.store.dispatch(ProductsActions.loadProducts({
      page: this.currentPage,
      limit: this.limit,
      filter: { ...this.filter },
    }));
  }

  applyFilter(): void {
    this.currentPage = 1;
    this.store.dispatch(ProductsActions.setFilter({ filter: { ...this.filter } }));
    this.loadProducts();
  }

  clearFilter(): void {
    this.filter = {};
    this.currentPage = 1;
    this.store.dispatch(ProductsActions.clearFilter());
    this.loadProducts();
  }

  nextPage(): void {
    this.currentPage++;
    this.loadProducts();
  }

  prevPage(): void {
    if (this.currentPage > 1) {
      this.currentPage--;
      this.loadProducts();
    }
  }
}
