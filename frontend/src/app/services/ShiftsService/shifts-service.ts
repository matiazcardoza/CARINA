import { Injectable, inject } from '@angular/core';
import { environment } from '../../../environments/environment';
import { map, Observable } from 'rxjs';
import { HttpClient } from '@angular/common/http';

interface ShiftsElement{
  id: number;
  name: string;
} 

interface ShiftsApiResponse {
  message: string;
  data: ShiftsElement[];
}

@Injectable({
  providedIn: 'root'
})
export class ShiftsService {
  private http = inject(HttpClient);
  private apiUrl = environment.BACKEND_URL;

  getShifts(): Observable<ShiftsElement[]> {
    return this.http.get<ShiftsApiResponse>(`${this.apiUrl}/api/shifts-select`, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }
}
