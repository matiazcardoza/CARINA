import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class ExmapleService {
  private apiUrl = 'http://127.0.0.1:8000/api/example';
  constructor(private http: HttpClient) {}
  sendData(data: any): Observable<any> {
    return this.http.post<any>(this.apiUrl, data);
  }
}
