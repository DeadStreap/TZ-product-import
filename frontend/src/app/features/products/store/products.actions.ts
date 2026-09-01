import { createAction, props } from '@ngrx/store';
import { ProductListItem, ProductFilter, PaginatedResponse, Product } from '@app/shared/models/product.model';

export const loadProducts = createAction(
  '[Products] Load Products',
  props<{ page: number; limit: number; filter?: ProductFilter }>()
);

export const loadProductsSuccess = createAction(
  '[Products] Load Products Success',
  props<{ response: PaginatedResponse<ProductListItem> }>()
);

export const loadProductsFailure = createAction(
  '[Products] Load Products Failure',
  props<{ error: string }>()
);

export const loadProduct = createAction(
  '[Products] Load Product',
  props<{ id: number }>()
);

export const loadProductSuccess = createAction(
  '[Products] Load Product Success',
  props<{ product: Product }>()
);

export const loadProductFailure = createAction(
  '[Products] Load Product Failure',
  props<{ error: string }>()
);

export const setPage = createAction(
  '[Products] Set Page',
  props<{ page: number }>()
);

export const setFilter = createAction(
  '[Products] Set Filter',
  props<{ filter: ProductFilter }>()
);

export const clearFilter = createAction('[Products] Clear Filter');
