import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { map, Observable } from 'rxjs';
import { WorkLogElement } from '../../features/private/daily-work-log/daily-work-log';
import { environment } from '../../../environments/environment';
import { WorkLogIdElement } from '../../features/private/daily-work-log/daily-work-log-id/daily-work-log-id';
import { WorkLogDataElement } from '../../features/private/reports/reports';
import { EvidenceDataElement } from '../../features/private/reports/view/view-evidence/view-evidence';
import { DocumentDailyPartElement } from '../../features/private/daily-work-log/daily-work-log-id/form/daily-work-signature/daily-work-signature';

interface WorkLogApiResponse {
  message: string;
  data: WorkLogElement[];
}

interface WorkLogIdApiResponse {
  message: string;
  data: WorkLogIdElement[];
}

interface WorkLogDataApiResponse {
  message: string;
  valoration: ValorationData;
  data: WorkLogDataElement[];
}

interface EvidenceDataApiResponse {
  message: string;
  data: EvidenceDataElement[];
}

interface DocumentDailyPartApiResponse {
  message: string;
  data: DocumentDailyPartElement;
  pages: number;
}


interface SingleApiResponse {
  message: string;
  data: WorkLogElement;
}

export interface CreateWorkLogData {
  work_date: string;
  start_time: string;
  initial_fuel: number;
}

export interface SendDocumentData {
  userId: number;
  documentId: number | null;
}

export interface ValorationMachinery {
  service_id: number;
  equipment: any;
  time_worked: string;
  equivalent_hours: number;
  cost_per_hour: number;
  total_amount: number;
  cost_per_day: number;
  days_worked: number;
}

export interface ValorationGoal {
  goal_id?: number;
  goal_code?: string;
  goal_detail: string;
}

export interface ValorationData {
  goal: ValorationGoal;
  machinery: ValorationMachinery[];
  valoration_amount: number;
}

@Injectable({
  providedIn: 'root'
})
export class DailyWorkLogService {

  private http = inject(HttpClient);
  private apiUrl = environment.BACKEND_URL;

  getOrdersWorkLogData(): Observable<WorkLogElement[]> {
    return this.http.get<WorkLogApiResponse>(`${this.apiUrl}/api/services`, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  importOrder(payload: any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/api/orders-silucia/import-order`, payload, {
      withCredentials: true
    });
  }

  getWorkLogData(id: number, date?: string, shift: number | string = 'all'): Observable<WorkLogIdElement[]> {
    const params: any = {
      ...(date && { date }),
      ...(shift !== 'all' && { shift_id: shift })
    };
    return this.http.get<WorkLogIdApiResponse>(`${this.apiUrl}/api/daily-work-log/${id}`, {
      withCredentials: true,
      params: params
    }).pipe(
      map(response => response.data)
    );
  }

  createWorkLog(workLogData: CreateWorkLogData): Observable<SingleApiResponse> {
    return this.http.post<SingleApiResponse>(`${this.apiUrl}/api/daily-work-log`, workLogData, {
      withCredentials: true
    });
  }

  updateWorkLog( workLogData: CreateWorkLogData): Observable<WorkLogElement> {
    return this.http.put<SingleApiResponse>(`${this.apiUrl}/api/daily-work-log/`, workLogData, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  deleteWorkLog(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/api/daily-work-log-delete/${id}`, {
      withCredentials: true
    });
  }

  deleteService(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/api/daily-service-delete/${id}`, {
      withCredentials: true
    });
  }

  completeWorkLog(formData: FormData): Observable<WorkLogElement> {
    return this.http.post<SingleApiResponse>(`${this.apiUrl}/api/daily-work-log/complete`, formData, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  generatePdf(id: number, date?: string, shift: number | string = 'all'): Observable<WorkLogElement> {
    const params: any = {
      ...(date && { date }),
      ...(shift !== 'all' && { shift_id: shift })
    };
    return this.http.post<SingleApiResponse>(`${this.apiUrl}/api/daily-work-log/${id}/generate-pdf`, {}, {
      withCredentials: true,
      params: params
    }).pipe(
      map(response => response.data)
    );
  }

  getOrderByNumber(orderNumber: string, anio: string): Observable<any> {
    const apiUrl = `https://sistemas.regionpuno.gob.pe/siluciav2-api/api/ordenserviciodetallado?anio=${anio}&numero=${orderNumber}`;
    return this.http.get<any>(apiUrl);
  }

  getSelectedServiceData(): Observable<WorkLogElement[]> {
    return this.http.get<WorkLogApiResponse>(`${this.apiUrl}/api/services/selected`, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  getDailyPartData(codGoal: number): Observable<{ valoration: ValorationData, data: WorkLogDataElement[] }> {
    return this.http.get<WorkLogDataApiResponse>(`${this.apiUrl}/api/services/daily-parts/${codGoal}`, {
      withCredentials: true,
    }).pipe(
      map(response => ({
        valoration: response.valoration,
        data: response.data
      }))
    );
  }

  getEvidenceData(serviceId: number, stateValorized: number): Observable<EvidenceDataElement[]> {
    return this.http.get<EvidenceDataApiResponse>(`${this.apiUrl}/api/daily-work-evendece/${serviceId}`, {
      withCredentials: true,
      params: { state_valorized: stateValorized.toString() }
    }).pipe(
      map(response => response.data)
    );
  }

  getWorkLogDocument(serviceId: number, date?: string, shift: number | string = 'all'): Observable<DocumentDailyPartElement> {
    return this.http.get<DocumentDailyPartApiResponse>(`${this.apiUrl}/api/daily-work-document/${serviceId}/${date}/${shift}`, {
      withCredentials: true,
    }).pipe(
      map(response => ({
        ...response.data,
        pages: response.pages
      }))
    );
  }

  sendDocument(data: SendDocumentData): Observable<any> {
    return this.http.post(`${this.apiUrl}/api/daily-work-document/send`, data, {
      withCredentials: true
    });
  }

  getIdmeta(mechanicalId: number): Observable<WorkLogElement[]> {
    console.log(mechanicalId);
    return this.http.get<WorkLogApiResponse>(`${this.apiUrl}/api/services/idmeta/${mechanicalId}`, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  updateIdmeta( workLogData: WorkLogElement): Observable<WorkLogElement> {
    return this.http.put<SingleApiResponse>(`${this.apiUrl}/api/services/idmeta-update`, workLogData, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }
}
