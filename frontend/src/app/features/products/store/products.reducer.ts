import { createReducer, on } from '@ngrx/store';
import { ProductListItem, Product, ProductFilter } from '@app/shared/models/product.model';
import * as ProductsActions from './products.actions';

export interface ProductsState {
  items: ProductListItem[];
  selectedItem: Product | null;
  loading: boolean;
  error: string | null;
  page: number;
  limit: number;
  total: number;
  totalPages: number;
  filter: ProductFilter;
}

const initialState: ProductsState = {
  items: [],
  selectedItem: null,
  loading: false,
  error: null,
  page: 1,
  limit: 20,
  total: 0,
  totalPages: 0,
  filter: {},
};

export const productsReducer = createReducer(
  initialState,

  on(ProductsActions.loadProducts, (state, { page, limit, filter }) => ({
    ...state,
    loading: true,
    error: null,
    page,
    limit,
    filter: filter ?? state.filter,
  })),

  on(ProductsActions.loadProductsSuccess, (state, { response }) => ({
    ...state,
    loading: false,
    items: response.items,
    total: response.total,
    totalPages: response.total_pages,
    page: response.page,
  })),

  on(ProductsActions.loadProductsFailure, (state, { error }) => ({
    ...state,
    loading: false,
    error,
  })),

  on(ProductsActions.loadProduct, state => ({
    ...state,
    selectedItem: null,
    loading: true,
  })),

  on(ProductsActions.loadProductSuccess, (state, { product }) => ({
    ...state,
    selectedItem: product,
    loading: false,
  })),

  on(ProductsActions.loadProductFailure, (state, { error }) => ({
    ...state,
    loading: false,
    error,
  })),

  on(ProductsActions.setPage, (state, { page }) => ({
    ...state,
    page,
  })),

  on(ProductsActions.setFilter, (state, { filter }) => ({
    ...state,
    filter,
    page: 1,
  })),

  on(ProductsActions.clearFilter, state => ({
    ...state,
    filter: {},
    page: 1,
  })),
);
