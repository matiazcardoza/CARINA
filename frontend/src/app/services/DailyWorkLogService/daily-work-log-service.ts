import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { map, Observable } from 'rxjs';
import { WorkLogElement } from '../../features/private/daily-work-log/daily-work-log';
import { environment } from '../../../environments/environment';
import { WorkLogIdElement } from '../../features/private/daily-work-log/daily-work-log-id/daily-work-log-id';

interface WorkLogApiResponse {
  message: string;
  data: WorkLogElement[];
}

interface WorkLogIdApiResponse {
  message: string;
  data: WorkLogIdElement[];
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

@Injectable({
  providedIn: 'root'
})
export class DailyWorkLogService {

  private http = inject(HttpClient);
  private apiUrl = environment.BACKEND_URL;

  getOrdersWorkLogData(): Observable<WorkLogElement[]> {
    return this.http.get<WorkLogApiResponse>(`${this.apiUrl}/api/orders-silucia`, {
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

  getWorkLogData(id: number): Observable<WorkLogIdElement[]> {
    return this.http.get<WorkLogIdApiResponse>(`${this.apiUrl}/api/daily-work-log/${id}`, {
      withCredentials: true
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

  updateWorkLog(id: number, workLogData: CreateWorkLogData): Observable<WorkLogElement> {
    return this.http.put<SingleApiResponse>(`${this.apiUrl}/api/daily-work-log/${id}`, workLogData, {
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

  generatePdf(id: number): Observable<Blob> {
    return this.http.post(`${this.apiUrl}/api/daily-work-log/${id}/generate-pdf`, {}, {
      responseType: 'blob',
      withCredentials: true
    });
  }

  getOrderByNumber(orderNumber: string): Observable<any> {
    const apiUrl = `https://sistemas.regionpuno.gob.pe/siluciav2-api/api/ordenserviciodetallado?numero=${orderNumber}`;
    return this.http.get<any>(apiUrl);
  }
}