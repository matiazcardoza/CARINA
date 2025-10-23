import { Injectable, inject } from '@angular/core';
import { environment } from '../../../environments/environment';
import { map, Observable } from 'rxjs';
import { HttpClient } from '@angular/common/http';


@Injectable({
  providedIn: 'root'
})
export class ReportsServicesService {

  private http = inject(HttpClient);
  private apiUrl = environment.BACKEND_URL;

  generateRequest(id: number): Observable<Blob> {
    return this.http.post(`${this.apiUrl}/api/services/${id}/generate-request`, {}, {
      responseType: 'blob',
      withCredentials: true
    });
  }

  generateAuth(id: number): Observable<Blob> {
    return this.http.post(`${this.apiUrl}/api/services/${id}/generate-auth`, {}, {
      responseType: 'blob',
      withCredentials: true
    });
  }

  generateLiquidation(id: number): Observable<Blob> {
    return this.http.post(`${this.apiUrl}/api/services/${id}/generate-liquidation`, {}, {
      responseType: 'blob',
      withCredentials: true
    });
  }  

  openDocumentInNewTab(path: string): void {
    const url = `${this.apiUrl}/storage/${path}`;
    window.open(url, '_blank');
  }
}
