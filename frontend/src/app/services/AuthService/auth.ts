import { Injectable } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable, BehaviorSubject, throwError } from 'rxjs';
import { tap, switchMap, catchError } from 'rxjs/operators';
import { Router } from '@angular/router';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = environment.BACKEND_URL;
  private isAuthenticatedSubject = new BehaviorSubject<boolean>(false);
  isAuthenticated$ = this.isAuthenticatedSubject.asObservable();

  constructor(private http: HttpClient, private router: Router) {
    this.verifyAuthentication().subscribe({
      next: () => this.isAuthenticatedSubject.next(true),
      error: () => this.isAuthenticatedSubject.next(false)
    });
  }

  getCsrfToken(): Observable<any> {
    return this.http.get(`${this.apiUrl}/sanctum/csrf-cookie`, { 
      withCredentials: true 
    }).pipe(
      catchError(error => throwError(() => error))
    );
  }

  login(credentials: any): Observable<any> {
    return this.getCsrfToken().pipe(
      switchMap(() => {
        const loginData = {
          email: credentials.email,
          password: credentials.password
        };
        return this.http.post(`${this.apiUrl}/login`, loginData, { 
          withCredentials: true
        });
      }),
      tap(() => this.isAuthenticatedSubject.next(true)),
      catchError((error: HttpErrorResponse) => {
        this.isAuthenticatedSubject.next(false);
        return throwError(() => error);
      })
    );
  }

  register(data: any): Observable<any> {
    return this.getCsrfToken().pipe(
      switchMap(() => {
        const registerData = {
          name: data.name,
          email: data.email,
          password: data.password,
          password_confirmation: data.password_confirmation
        };
        
        return this.http.post(`${this.apiUrl}/register`, registerData, { 
          withCredentials: true
        });
      }),
      tap(() => this.isAuthenticatedSubject.next(true)),
      catchError((error: HttpErrorResponse) => {
        this.isAuthenticatedSubject.next(false);
        return throwError(() => error);
      })
    );
  }

  logout(): Observable<any> {
    return this.http.post(`${this.apiUrl}/logout`, {}, { 
      withCredentials: true
    }).pipe(
      tap(() => {
        this.isAuthenticatedSubject.next(false);
        this.router.navigate(['/login']);
      }),
      catchError(error => {
        this.isAuthenticatedSubject.next(false);
        return throwError(() => error);
      })
    );
  }

  verifyAuthentication(): Observable<any> {
    return this.http.get(`${this.apiUrl}/api/user`, { 
      withCredentials: true 
    }).pipe(
      tap(() => this.isAuthenticatedSubject.next(true)),
      catchError(() => {
        this.isAuthenticatedSubject.next(false);
        return throwError(() => new Error('Not authenticated'));
      })
    );
  }

  getCurrentUser(): Observable<any> {
    return this.http.get(`${this.apiUrl}/api/user`, {
      withCredentials: true
    });
  }
}