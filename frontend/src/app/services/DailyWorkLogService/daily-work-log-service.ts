import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { map, Observable } from 'rxjs';
import { WorkLogElement } from '../../features/private/daily-work-log/daily-work-log';
import { environment } from '../../../environments/environment';
import { WorkLogIdElement } from '../../features/private/daily-work-log/daily-work-log-id/daily-work-log-id';
import { WorkLogDataElement } from '../../features/private/dashboards/dashboards';
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

  importOrder(orderData: any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/api/orders-silucia/import-order`, orderData, {
      withCredentials: true
    });
  }

  getWorkLogData(id: number, date?: string): Observable<WorkLogIdElement[]> {
    return this.http.get<WorkLogIdApiResponse>(`${this.apiUrl}/api/daily-work-log/${id}`, {
      withCredentials: true,
      params: date ? { date } : {}
    }).pipe(
      map(response => response.data)
    );
  }

  createWorkLog(workLogData: CreateWorkLogData): Observable<WorkLogElement> {
    return this.http.post<SingleApiResponse>(`${this.apiUrl}/api/daily-work-log`, workLogData, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  updateWorkLog( workLogData: CreateWorkLogData): Observable<WorkLogElement> {
    return this.http.put<SingleApiResponse>(`${this.apiUrl}/api/daily-work-log/`, workLogData, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  deleteWorkLog(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/api/daily-work-log/${id}`, {
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

  generatePdf(id: number, date?: string): Observable<WorkLogElement> {
    return this.http.post<SingleApiResponse>(`${this.apiUrl}/api/daily-work-log/${id}/generate-pdf`, {}, {
      withCredentials: true,
      params: date ? { date } : {}
    }).pipe(
      map(response => response.data)
    );
  }

  getOrderByNumber(orderNumber: string): Observable<any> {
    const apiUrl = `https://sistemas.regionpuno.gob.pe/siluciav2-api/api/ordenserviciodetallado?numero=${orderNumber}`;
    return this.http.get<any>(apiUrl);
  }

  getSelectedServiceData(): Observable<WorkLogElement[]> {
    return this.http.get<WorkLogApiResponse>(`${this.apiUrl}/api/services/selected`, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  getDailyPartData(codGoal: number): Observable<WorkLogDataElement[]> {
    return this.http.get<WorkLogDataApiResponse>(`${this.apiUrl}/api/services/daily-parts/${codGoal}`, {
      withCredentials: true,
    }).pipe(
      map(response => response.data)
    );
  }

  getEvidenceData(serviceId: number): Observable<EvidenceDataElement[]> {
    return this.http.get<EvidenceDataApiResponse>(`${this.apiUrl}/api/daily-work-evendece/${serviceId}`, {
      withCredentials: true,
    }).pipe(
      map(response => response.data)
    );
  }

  liquidarServicio(serviceId: number): Observable<WorkLogDataElement[]> {
    return this.http.post<WorkLogDataApiResponse>(`${this.apiUrl}/api/services/liquidar-servicio/${serviceId}`, {}, {
      withCredentials: true,
    }).pipe(
      map(response => response.data)
    );
  }

  getWorkLogDocument(serviceId: number, date?: string): Observable<DocumentDailyPartElement> {
    return this.http.get<DocumentDailyPartApiResponse>(`${this.apiUrl}/api/daily-work-document/${serviceId}/${date}`, {
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
}
