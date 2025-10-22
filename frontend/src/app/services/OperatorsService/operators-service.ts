import { Injectable, inject } from '@angular/core';
import { environment } from '../../../environments/environment';
import { map, Observable } from 'rxjs';
import { HttpClient } from '@angular/common/http';

export interface OperatorsElement{
  id: number;
  name: string;
  num_doc: string;
  state: number;
}

interface OperatorsApiResponse {
  message: string;
  data: OperatorsElement[];
}

@Injectable({
  providedIn: 'root'
})
export class OperatorsService {
  private http = inject(HttpClient);
  private apiUrl = environment.BACKEND_URL;

  getOperators(serviceId: number | string): Observable<OperatorsElement[]> {
    return this.http.get<OperatorsApiResponse>(`${this.apiUrl}/api/operators-select/${serviceId}`, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }
}
