import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { map, Observable } from 'rxjs';
import { WorkLogElement } from '../../features/private/daily-work-log/daily-work-log';
import { environment } from '../../../environments/environment';

interface ApiResponse {
  message: string;
  data: WorkLogElement[];
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

  getWorkLogData(): Observable<WorkLogElement[]> {
    return this.http.get<ApiResponse>(`${this.apiUrl}/api/daily-work-log`, {
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
    console.log('Submitting work log:');
    formData.forEach((value, key) => {
      console.log(key, value);
    });
    
    // NO agregues Content-Type header manualmente para FormData
    return this.http.post<SingleApiResponse>(`${this.apiUrl}/api/daily-work-log/complete`, formData, {
      withCredentials: true
      // No incluir headers: { 'Content-Type': ... }
    }).pipe(
      map(response => response.data)
    );
  }
}