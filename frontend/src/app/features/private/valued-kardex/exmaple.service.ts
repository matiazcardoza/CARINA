import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { environment } from '../../../../environments/environment';
@Injectable({
  providedIn: 'root'
})
export class ExmapleService {
  // private apiUrl = environment.BACKEND_URL;
  private apiUrl = 'http://localhost:8000/api/example';
  constructor(private http: HttpClient) {}

  sendData(data: any): Observable<any> {
    // const csrfToken = 'ddddddddddddd';
    // const headers = new HttpHeaders({
    //   'XSRF-TOKEN': csrfToken
    // });

    const options = {
      // headers: headers,
      withCredentials: true
      
    };
    return this.http.post<any>(this.apiUrl, data, options)

    
    // return this.http.post<any>(this.apiUrl,data)
    // .pipe(
    //       map(response => response.data)
    // );
  }
}


export class ExmapleServicex {
  // private apiUrl = environment.BACKEND_URL;
  private apiUrl = 'http://localhost:8000/api/example';
  constructor(private http: HttpClient) {}

  sendData(data: any): Observable<any> {
    // const csrfToken = 'ddddddddddddd';
    // const headers = new HttpHeaders({
    //   'XSRF-TOKEN': csrfToken
    // });

    const options = {
      // headers: headers,
      withCredentials: true
      
    };
    return this.http.post<any>(this.apiUrl, data, options)

    
    // return this.http.post<any>(this.apiUrl,data)
    // .pipe(
    //       map(response => response.data)
    // );
  }
}

// import { Injectable, inject } from '@angular/core';
// import { HttpClient } from '@angular/common/http';
// // import { map } fro 'rxjs/operators';
// import { map } from 'rxjs';
// import { Observable } from 'rxjs';
// // import { WorkLogElement } from '../../features/private/daily-work-log/daily-work-log';
// import { WorkLogElement } from '../daily-work-log/daily-work-log';
// // import { environment } from '../../../environments/environment';
// import { environment } from '../../../../environments/environment';

// interface ApiResponse {
//   message: string;
//   data: WorkLogElement[];
// }

// @Injectable({
//   providedIn: 'root'
// })
// export class ExmapleService {

//   private http = inject(HttpClient);
//   private apiUrl = environment.BACKEND_URL;

//   constructor() { }

//   sendData(data:any): Observable<WorkLogElement[]> {
//     return this.http.post<ApiResponse>(`${this.apiUrl}/api/example`,data, {
//       withCredentials: true
//     }).pipe(
//       map(response => response.data)
//     );
//   }
// }
