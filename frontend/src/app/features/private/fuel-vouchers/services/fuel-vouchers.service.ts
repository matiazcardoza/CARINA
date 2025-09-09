import { inject, Injectable } from '@angular/core';
import { FuelOrderListItem } from '../interfaces/fuel-vouchers.interface';
import { Observable } from 'rxjs';
import { HttpClient, HttpParams } from '@angular/common/http';
import { environment } from '../../../../../environments/environment';

export interface Page<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface FuelOrderQuery {
  all?: boolean;
  numero?: string;
  placa?: string;
  page?: number;
}

@Injectable({
  providedIn: 'root'
})
export class FuelVouchersService {

    private apiUrl = environment.BACKEND_URL;
    private options = {withCredentials: true};
    private http = inject(HttpClient);
    
    // private baseUrl = '/api/fuel-orders';


    list(params?: FuelOrderQuery): Observable<Page<FuelOrderListItem>> {
      const endpoint = `${this.apiUrl}/api/fuel-orders`;
      let httpParams = new HttpParams();
      if (params?.all)   httpParams = httpParams.set('all', '1');
      if (params?.numero) httpParams = httpParams.set('numero', params.numero);
      if (params?.placa)  httpParams = httpParams.set('placa', params.placa);
      if (params?.page)   httpParams = httpParams.set('page', String(params.page));

      // Si usas Sanctum SPA con cookies, agrega withCredentials:true en tu provideHttpClient global
      return this.http.get<Page<FuelOrderListItem>>(endpoint, { ...this.options,params: httpParams});
    }

    show(id: number): Observable<FuelOrderListItem> {
      const endpoint = `${this.apiUrl}/api/fuel-orders`;
      return this.http.get<FuelOrderListItem>(`${endpoint}/${id}`);
    }

    create(body: any): Observable<FuelOrderListItem> {
      const endpoint = `${this.apiUrl}/api/fuel-orders`;
      return this.http.post<FuelOrderListItem>(endpoint, body, this.options);
    }
}
