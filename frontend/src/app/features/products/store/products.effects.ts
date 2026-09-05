import { Injectable, inject } from '@angular/core';
import { Actions, createEffect, ofType } from '@ngrx/effects';
import { of } from 'rxjs';
import { map, catchError, switchMap } from 'rxjs/operators';
import { ApiService } from '@app/core/services/api.service';
import * as ProductsActions from './products.actions';

@Injectable()
export class ProductsEffects {
  private actions$ = inject(Actions);
  private api = inject(ApiService);

  loadProducts$ = createEffect(() =>
    this.actions$.pipe(
      ofType(ProductsActions.loadProducts),
      switchMap(({ page, limit, filter }) =>
        this.api.getProducts(page, limit, filter).pipe(
          map(response => ProductsActions.loadProductsSuccess({ response })),
          catchError(error =>
            of(ProductsActions.loadProductsFailure({ error: error.error?.error || error.message || 'Failed to load products' }))
          )
        )
      )
    )
  );

  loadProduct$ = createEffect(() =>
    this.actions$.pipe(
      ofType(ProductsActions.loadProduct),
      switchMap(({ id }) =>
        this.api.getProduct(id).pipe(
          map(product => ProductsActions.loadProductSuccess({ product })),
          catchError(error =>
            of(ProductsActions.loadProductFailure({ error: error.error?.error || error.message || 'Failed to load product' }))
          )
        )
      )
    )
  );
}
