import { Injectable, inject } from '@angular/core';
import { environment } from '../../../environments/environment';
import { map, Observable } from 'rxjs';
import { HttpClient } from '@angular/common/http';

export interface ProductsElement{
  id: number;
  numero: string;
  item: string;
}

interface ProductsApiResponse {
  message: string;
  data: ProductsElement[];
}

@Injectable({
  providedIn: 'root'
})
export class ProductsService {
  private http = inject(HttpClient);
  private apiUrl = environment.BACKEND_URL;

  getProducts(): Observable<ProductsElement[]> {
    return this.http.get<ProductsApiResponse>(`${this.apiUrl}/api/products-select`, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }
}
