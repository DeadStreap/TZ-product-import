import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '@env/environment';
import { Product, ProductListItem, ProductFilter, PaginatedResponse } from '@app/shared/models/product.model';
import { ImportTaskStatus } from '@app/shared/models/import-result.model';

@Injectable({ providedIn: 'root' })
export class ApiService {
  private readonly apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  login(email: string, password: string): Observable<{ token: string }> {
    return this.http.post<{ token: string }>(`${this.apiUrl}/auth/login`, { email, password });
  }

  getProducts(
    page: number = 1,
    limit: number = 20,
    filter?: ProductFilter
  ): Observable<PaginatedResponse<ProductListItem>> {
    let params = new HttpParams()
      .set('page', page.toString())
      .set('limit', limit.toString());

    if (filter?.name) {
      params = params.set('name', filter.name);
    }
    if (filter?.minPrice !== undefined) {
      params = params.set('minPrice', filter.minPrice.toString());
    }
    if (filter?.maxPrice !== undefined) {
      params = params.set('maxPrice', filter.maxPrice.toString());
    }

    return this.http.get<PaginatedResponse<ProductListItem>>(`${this.apiUrl}/products`, { params });
  }

  getProduct(id: number): Observable<Product> {
    return this.http.get<Product>(`${this.apiUrl}/products/${id}`);
  }

  importFile(file: File): Observable<{ task_id: number; status: string }> {
    const formData = new FormData();
    formData.append('file', file);
    return this.http.post<{ task_id: number; status: string }>(`${this.apiUrl}/import`, formData);
  }

  getImportStatus(taskId: number): Observable<ImportTaskStatus> {
    return this.http.get<ImportTaskStatus>(`${this.apiUrl}/import/${taskId}/status`);
  }
}
