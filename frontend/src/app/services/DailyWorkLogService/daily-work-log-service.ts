import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { map } from 'rxjs/operators';
import { Observable } from 'rxjs';
import { WorkLogElement } from '../../features/private/daily-work-log/daily-work-log';
import { environment } from '../../../environments/environment';

interface ApiResponse {
  message: string;
  data: WorkLogElement[];
}

@Injectable({
  providedIn: 'root'
})
export class DailyWorkLogService {

  private http = inject(HttpClient);
  private apiUrl = environment.BACKEND_URL;

  constructor() { }

  getWorkLogData(): Observable<WorkLogElement[]> {
    return this.http.get<ApiResponse>(`${this.apiUrl}/api/daily-work-log`, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }
}