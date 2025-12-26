import { Injectable, inject } from '@angular/core';
import { environment } from '../../../environments/environment';
import { map, Observable } from 'rxjs';
import { HttpClient } from '@angular/common/http';
import { LiquidationElement } from '../../features/private/reports/reports-id/reports-id';
import { ValorationElement } from '../../features/private/reports/view/report-valorized/report-valorized';

interface LiquidationApiResponse {
  message: string;
  data: LiquidationElement;
}

interface ValorationApiResponse {
  message: string;
  data: ValorationElement;
}

interface SaveChangesResponse {
  message: string;
  success: boolean;
  data?: any;
}

interface AdjustmentHistory {
  id: number;
  num_reg: number;
  created_at: string;
  updated_at: string;
  updated_by: number;
  adjusted_data: {
    equipment: any;
    request: any;
    auth: any;
    liquidation: any;
  };
}

interface AdjustmentHistoryValoration {
  id: number;
  num_reg: number;
  created_at: string;
  updated_at: string;
  updated_by: number;
  valoration_data: {
    goal: any;
    machinery: any;
    valoration_amount: any;
    deductives?: any;
  };
}

interface AdjustmentHistoryResponse {
  message: string;
  data: AdjustmentHistory[];
}

interface AdjustmentHistoryValorationResponse {
  message: string;
  data: AdjustmentHistoryValoration[];
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

  generateValorization(valorationData: any): Observable<Blob> {
    return this.http.post(`${this.apiUrl}/api/reports/report-generate-valorization`, valorationData, {
      responseType: 'blob',
      withCredentials: true
    });
  }
  generateDeductives(deductivesData: any): Observable<Blob> {
    return this.http.post(`${this.apiUrl}/api/reports/report-generate-deductives`, deductivesData, {
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

  saveValorization(valorationData: any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/api/reports/save-valoration`, valorationData, {
      withCredentials: true
    });
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

  getValorationData(goalId: number): Observable<ValorationElement> {
    return this.http.get<ValorationApiResponse>(`${this.apiUrl}/api/report/valoration/${goalId}`, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  getAdjustedLiquidationData(serviceId: number): Observable<AdjustmentHistory[]> {
    return this.http.get<AdjustmentHistoryResponse>(
      `${this.apiUrl}/api/report-id/adjusted-liquidation/${serviceId}`,
      { withCredentials: true }
    ).pipe(
      map(response => response.data)
    );
  }

  getAdjustedValorationData(goalId: number): Observable<AdjustmentHistoryValoration[]> {
    return this.http.get<AdjustmentHistoryValorationResponse>(
      `${this.apiUrl}/api/report/adjusted-valoration/${goalId}`,
      { withCredentials: true }
    ).pipe(
      map(response => response.data)
    );
  }

  downloadAllCompletedDailyParts(serviceId: number, stateValorized: number): Observable<Blob> {
    return this.http.post(`${this.apiUrl}/api/reports/download-merged-daily-parts/${serviceId}/${stateValorized}`, {}, {
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

  getOrderByNumber(orderNumber: string, anio: string, tipoOrden: 'servicio' | 'compra'): Observable<any> {
    const apiUrl = tipoOrden === 'servicio' 
      ? `https://sistemas.regionpuno.gob.pe/siluciav2-api/api/ordenserviciodetallado?anio=${anio}&numero=${orderNumber}`
      : `https://sistemas.regionpuno.gob.pe/siluciav2-api/api/ordencompradetallado?anio=${anio}&numero=${orderNumber}`;
    
    return this.http.get<any>(apiUrl);
  }
}
