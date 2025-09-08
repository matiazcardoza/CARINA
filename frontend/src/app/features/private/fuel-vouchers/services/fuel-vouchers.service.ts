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
    private base = '/api/fuel-orders';
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



  /** Genera el PDF y crea el flujo (devuelve el PDF como blob para descargar/abrir). */
  generateReport(orderId: number): Observable<Blob> {
    // backend sugerido: POST /api/fuel-orders/{id}/generate-report
    return this.http.post(`${this.apiUrl}/api/fuel-orders/${orderId}/generate-report`, {}, { ...this.options, responseType: 'blob' as const });
  }

  /** Obtiene estado del reporte/flujo de firma para el vale. */
  getSignatureStatus(orderId: number): Observable<any> {
    // opciones:
    // - GET /api/fuel-orders/{id}/report   (devuelve report + flow + steps + can_sign + download_url)
    // - o genérico: GET /api/reports/by?reportable_type=App\\Models\\FuelOrder&reportable_id={id}
    return this.http.get(`${this.apiUrl}/api/fuel-orders/${orderId}/report`, this.options);
  }

  /** Firma usando el callback (si no tienes URL directa, arma el endpoint con flow/step/token). */
  signWithFlowStep(flowId: number, stepId: number, token: string) {
    // backend sugerido (como ya usas en Kardex): POST /api/signatures/callback?flow_id=&step_id=&token=
    return this.http.post(`/api/signatures/callback?flow_id=${flowId}&step_id=${stepId}&token=${encodeURIComponent(token)}`, {});
  }

  /** Si necesitas descargar por un endpoint específico del vale (no obligatorio si ya tienes download_url). */
  downloadReport(orderId: number): Observable<Blob> {
    return this.http.get(`${this.base}/${orderId}/report/download`, { responseType: 'blob' as const });
  }
}
