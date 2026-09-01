import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { Store } from '@ngrx/store';
import { Observable } from 'rxjs';
import { Product } from '@app/shared/models/product.model';
import * as ProductsActions from '../store/products.actions';
import { selectSelectedProduct, selectProductsLoading } from '../store/products.selectors';

@Component({
  selector: 'app-product-card',
  standalone: true,
  imports: [CommonModule, RouterLink],
  template: `
    <div class="max-w-4xl mx-auto">
      <a routerLink="/products"
         class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-6 transition-colors">
        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back to products
      </a>

      @if (loading$ | async) {
        <div class="text-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p class="mt-4 text-gray-500">Loading product...</p>
        </div>
      } @else {
        @if (product$ | async; as product) {
        <div class="bg-white shadow rounded-lg overflow-hidden">
          <!-- Header -->
          <div class="px-6 py-5 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
              <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ product.name }}</h1>
                <p class="mt-1 text-sm text-gray-500">SKU: {{ product.external_code }}</p>
              </div>
              <div class="text-right">
                <div class="text-3xl font-bold text-gray-900">
                  {{ product.price | number:'1.2-2' }}
                </div>
                @if (product.discount !== null) {
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                               bg-green-100 text-green-800 mt-1">
                    {{ product.discount | number:'1.1-1' }}% off
                  </span>
                }
              </div>
            </div>
          </div>

          <!-- Images -->
          @if (product.images.length > 0) {
            <div class="px-6 py-5 border-b border-gray-200">
              <h2 class="text-lg font-medium text-gray-900 mb-4">Images</h2>
              <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                @for (image of product.images; track image.id) {
                  <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden group">
                    <img [src]="image.path ? '/uploads/' + image.path : image.url"
                         [alt]="product.name"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                         loading="lazy"
                         (error)="onImageError($event)" />
                  </div>
                }
              </div>
            </div>
          }

          <!-- Description -->
          @if (product.description) {
            <div class="px-6 py-5 border-b border-gray-200">
              <h2 class="text-lg font-medium text-gray-900 mb-2">Description</h2>
              <p class="text-gray-600 whitespace-pre-line leading-relaxed">{{ product.description }}</p>
            </div>
          }

          <!-- Attributes -->
          @if (product.attributes.length > 0) {
            <div class="px-6 py-5">
              <h2 class="text-lg font-medium text-gray-900 mb-4">Attributes</h2>
              <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @for (attr of product.attributes; track attr.id) {
                  <div class="bg-gray-50 rounded-lg p-3">
                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                      {{ attr.key }}
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900">
                      {{ attr.value || '-' }}
                    </dd>
                  </div>
                }
              </dl>
            </div>
          }

          <!-- Meta -->
          <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <dl class="flex flex-wrap gap-x-8 gap-y-2 text-xs text-gray-500">
              <div>
                <dt class="font-medium">Created</dt>
                <dd>{{ product.created_at | date:'medium' }}</dd>
              </div>
              <div>
                <dt class="font-medium">Updated</dt>
                <dd>{{ product.updated_at | date:'medium' }}</dd>
              </div>
              @if (product.purchase_price !== null) {
                <div>
                  <dt class="font-medium">Purchase Price</dt>
                  <dd>{{ product.purchase_price | number:'1.2-2' }}</dd>
                </div>
              }
            </dl>
          </div>
        </div>
        }
      }
    </div>
  `,
})
export class ProductCardComponent implements OnInit {
  private store = inject(Store);
  private route = inject(ActivatedRoute);

  product$: Observable<Product | null> = this.store.select(selectSelectedProduct);
  loading$: Observable<boolean> = this.store.select(selectProductsLoading);

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (id) {
      this.store.dispatch(ProductsActions.loadProduct({ id }));
    }
  }

  onImageError(event: Event): void {
    const img = event.target as HTMLImageElement;
    img.src = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiNmM2Y0ZjYiLz48dGV4dCB4PSI1MCIgeT0iNTAiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzlDQTNBRiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';
  }
}
