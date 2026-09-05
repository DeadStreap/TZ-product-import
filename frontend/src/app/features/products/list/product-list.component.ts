import { Component, OnInit, AfterViewInit, ViewChild, ElementRef, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { Store } from '@ngrx/store';
import { Observable, firstValueFrom } from 'rxjs';
import { ProductListItem, ProductFilter } from '@app/shared/models/product.model';
import { SpinnerComponent } from '@app/shared/spinner.component';
import * as ProductsActions from '../store/products.actions';
import {
  selectAllProducts,
  selectProductsLoading,
  selectProductsPage,
  selectProductsTotalPages,
  selectProductsFilter,
} from '../store/products.selectors';

@Component({
  selector: 'app-product-list',
  standalone: true,
  imports: [CommonModule, RouterLink, SpinnerComponent],
  template: `
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Products</h1>
        <a routerLink="/import"
           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium
                  rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
          Import New
        </a>
      </div>

      <div class="bg-white shadow rounded-lg p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label for="filter-name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input id="filter-name" type="text" #nameInput
                   placeholder="Search by name..."
                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                          text-sm" />
          </div>
          <div>
            <label for="filter-min-price" class="block text-sm font-medium text-gray-700 mb-1">Min Price</label>
            <input id="filter-min-price" type="number" #minPriceInput
                   placeholder="0"
                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                          text-sm" />
          </div>
          <div>
            <label for="filter-max-price" class="block text-sm font-medium text-gray-700 mb-1">Max Price</label>
            <input id="filter-max-price" type="number" #maxPriceInput
                   placeholder="99999"
                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm
                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                          text-sm" />
          </div>
          <div class="flex items-end gap-2">
            <button (click)="applyFilter(nameInput.value, minPriceInput.value, maxPriceInput.value)"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700
                           text-sm font-medium transition-colors">
              Apply
            </button>
            <button (click)="clearFilter(nameInput, minPriceInput, maxPriceInput)"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300
                           text-sm font-medium transition-colors">
              Clear
            </button>
          </div>
        </div>
      </div>

      @if (loading$ | async) {
        <div class="text-center py-12">
          <app-spinner size="lg" />
          <p class="mt-4 text-gray-500">Loading products...</p>
        </div>
      } @else {
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
                      {{ '$' + (product.price | number:'1.2-2') }}
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

        @if ((totalPages$ | async); as totalPages) {
          @if (totalPages > 1) {
            <div class="flex items-center justify-between">
              <button (click)="prevPage()" [disabled]="(page$ | async) === 1"
                      class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium
                             text-gray-700 bg-white hover:bg-gray-50
                             disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                Previous
              </button>
              <span class="text-sm text-gray-700">
                Page {{ page$ | async }} of {{ totalPages }}
              </span>
              <button (click)="nextPage()" [disabled]="(page$ | async) === totalPages"
                      class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium
                             text-gray-700 bg-white hover:bg-gray-50
                             disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                Next
              </button>
            </div>
          }
        }
      }
    </div>
  `,
})
export class ProductListComponent implements OnInit, AfterViewInit {
  private store = inject(Store);

  @ViewChild('nameInput') nameInput!: ElementRef<HTMLInputElement>;
  @ViewChild('minPriceInput') minPriceInput!: ElementRef<HTMLInputElement>;
  @ViewChild('maxPriceInput') maxPriceInput!: ElementRef<HTMLInputElement>;

  products$: Observable<ProductListItem[]> = this.store.select(selectAllProducts);
  loading$: Observable<boolean> = this.store.select(selectProductsLoading);
  page$: Observable<number> = this.store.select(selectProductsPage);
  totalPages$: Observable<number> = this.store.select(selectProductsTotalPages);

  private readonly limit = 20;

  async ngOnInit(): Promise<void> {
    const [page, filter] = await Promise.all([
      firstValueFrom(this.store.select(selectProductsPage)),
      firstValueFrom(this.store.select(selectProductsFilter)),
    ]);
    this.store.dispatch(ProductsActions.loadProducts({
      page,
      limit: this.limit,
      filter,
    }));
  }

  async ngAfterViewInit(): Promise<void> {
    const filter = await firstValueFrom(this.store.select(selectProductsFilter));
    if (filter.name) {
      this.nameInput.nativeElement.value = filter.name;
    }
    if (filter.minPrice != null) {
      this.minPriceInput.nativeElement.value = String(filter.minPrice);
    }
    if (filter.maxPrice != null) {
      this.maxPriceInput.nativeElement.value = String(filter.maxPrice);
    }
  }

  loadProducts(page?: number): void {
    const p = page ?? 1;
    this.store.dispatch(ProductsActions.loadProducts({
      page: p,
      limit: this.limit,
    }));
  }

  applyFilter(name: string, minPrice: string, maxPrice: string): void {
    const filter: ProductFilter = {};
    if (name) {
      filter.name = name;
    }
    if (minPrice) {
      filter.minPrice = +minPrice;
    }
    if (maxPrice) {
      filter.maxPrice = +maxPrice;
    }
    this.store.dispatch(ProductsActions.setFilter({ filter }));
    this.store.dispatch(ProductsActions.loadProducts({
      page: 1,
      limit: this.limit,
      filter,
    }));
  }

  clearFilter(nameInput: HTMLInputElement, minInput: HTMLInputElement, maxInput: HTMLInputElement): void {
    nameInput.value = '';
    minInput.value = '';
    maxInput.value = '';
    this.store.dispatch(ProductsActions.clearFilter());
    this.store.dispatch(ProductsActions.loadProducts({
      page: 1,
      limit: this.limit,
    }));
  }

  async nextPage(): Promise<void> {
    const page = await firstValueFrom(this.store.select(selectProductsPage));
    const totalPages = await firstValueFrom(this.store.select(selectProductsTotalPages));
    if (page < totalPages) {
      this.loadProducts(page + 1);
    }
  }

  async prevPage(): Promise<void> {
    const page = await firstValueFrom(this.store.select(selectProductsPage));
    if (page > 1) {
      this.loadProducts(page - 1);
    }
  }
}
