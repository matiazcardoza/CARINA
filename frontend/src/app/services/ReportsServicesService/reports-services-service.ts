import { Injectable, inject } from '@angular/core';
import { environment } from '../../../environments/environment';
import { map, Observable } from 'rxjs';
import { HttpClient } from '@angular/common/http';
import { LiquidationElement } from '../../features/private/reports/reports-id/reports-id';

interface LiquidationApiResponse {
  message: string;
  data: LiquidationElement;
}

interface SaveChangesResponse {
  message: string;
  success: boolean;
  data?: any;
}

@Injectable({
  providedIn: 'root'
})
export class ReportsServicesService {

  private http = inject(HttpClient);
  private apiUrl = environment.BACKEND_URL;

  generateRequest(formDataRequest: {serviceId: number; equipment:any; request:any}): Observable<Blob> {
    return this.http.post(`${this.apiUrl}/api/reports/report-generate-request`, formDataRequest, {
      responseType: 'blob',
      withCredentials: true
    });
  }

  generateAuth(formDataAuth: {serviceId: number; equipment:any; request:any; auth:any}): Observable<Blob> {
    return this.http.post(`${this.apiUrl}/api/reports/report-generate-auth`, formDataAuth, {
      responseType: 'blob',
      withCredentials: true
    });
  }

  generateLiquidation(formDataLiquidation: {serviceId: number; equipment:any; request:any; auth:any; liquidation:any}): Observable<Blob> {
    return this.http.post(`${this.apiUrl}/api/reports/report-generate-liquidation`, formDataLiquidation, {
      responseType: 'blob',
      withCredentials: true
    });
  }

  saveAuthChanges(changesData: {serviceId: number; equipment: any; request: any; auth: any; liquidation: any;
  }): Observable<SaveChangesResponse> {
    return this.http.post<SaveChangesResponse>(
      `${this.apiUrl}/api/reports/save-auth-changes`,
      changesData,
      { withCredentials: true }
    );
  }

  openDocumentInNewTab(path: string): void {
    const url = `${this.apiUrl}/storage/${path}`;
    window.open(url, '_blank');
  }

  getLiquidationData(id: number): Observable<LiquidationElement> {
    return this.http.get<LiquidationApiResponse>(`${this.apiUrl}/api/report-id/liquidation/${id}`, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  downloadAllCompletedDailyParts(serviceId: number): Observable<Blob> {
    console.log('Downloading all completed daily parts for service ID:', serviceId);
    return this.http.post(`${this.apiUrl}/api/reports/download-merged-daily-parts/${serviceId}`, {}, {
        responseType: 'blob',
        withCredentials: true
      }
    );
  }

  closeService(serviceId: number): Observable<any> {
    return this.http.post(`${this.apiUrl}/api/reports/close-service/${serviceId}`, {}, {
      withCredentials: true
    });
  }
}
