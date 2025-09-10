import { Injectable } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable, BehaviorSubject, throwError, map } from 'rxjs';
import { tap, switchMap, catchError } from 'rxjs/operators';
import { Router } from '@angular/router';
import { environment } from '../../../environments/environment';

export interface User {
  id: number;
  name: string;
  email: string;
  permissions: string[];
  roles: string[];
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = environment.BACKEND_URL;
  private isAuthenticatedSubject = new BehaviorSubject<boolean>(false);
  isAuthenticated$ = this.isAuthenticatedSubject.asObservable();

  private userPermissionsSubject = new BehaviorSubject<string[]>([]);
  userPermissions$ = this.userPermissionsSubject.asObservable();

  constructor(private http: HttpClient, private router: Router) {
    this.verifyAuthentication().subscribe({
      next: (user) => {
        this.isAuthenticatedSubject.next(true);
        this.userPermissionsSubject.next(user.permissions);
      },
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
      switchMap(() => this.getCurrentUser()),
      tap(user => {
        this.isAuthenticatedSubject.next(true);
        this.userPermissionsSubject.next(user.permissions);
      }),
      catchError((error: HttpErrorResponse) => {
        this.isAuthenticatedSubject.next(false);
        this.userPermissionsSubject.next([]);
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
        this.userPermissionsSubject.next([]);
        this.router.navigate(['/login']);
      }),
      catchError(error => {
        this.isAuthenticatedSubject.next(false);
        return throwError(() => error);
      })
    );
  }

  verifyAuthentication(): Observable<any> {
    return this.http.get<User>(`${this.apiUrl}/api/user`, { 
      withCredentials: true 
    }).pipe(
      tap((user) => {
        this.isAuthenticatedSubject.next(true);
        this.userPermissionsSubject.next(user.permissions);
      }),
      catchError(() => {
        this.isAuthenticatedSubject.next(false);
        this.userPermissionsSubject.next([]);
        return throwError(() => new Error('Not authenticated'));
      })
    );
  }

  getCurrentUser(): Observable<any> {
    return this.http.get(`${this.apiUrl}/api/user`, {
      withCredentials: true
    });
  }

  hasPermission(permission: string): Observable<boolean> {
    return this.userPermissions$.pipe(
      map(permissions => permissions.includes(permission))
    );
  }
}