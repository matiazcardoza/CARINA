import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class MovementRegisterService {
  private apiUrl = 'http://127.0.0.1:8000/examsdsdsdsdfssdsddfple';
  constructor(private http: HttpClient) {}
  sendData(data: any): Observable<any> {
    return this.http.post<any>(this.apiUrl, data);
  }
  seeUrl(){
    return this.apiUrl;
  }
}
