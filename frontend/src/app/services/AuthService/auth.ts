import { Injectable } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable, BehaviorSubject, throwError } from 'rxjs';
import { tap, switchMap, catchError } from 'rxjs/operators';
import { Router } from '@angular/router';
import { environment } from '../../../environments/environment';
import { PermissionService } from './permission';

export interface User {
  id: number;
  name: string;
  email: string;
  roles: string[];
  permissions: string[];
}

export interface LoginCredentials {
  num_doc: string;
  password: string;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = environment.BACKEND_URL;
  private isAuthenticatedSubject = new BehaviorSubject<boolean>(false);
  isAuthenticated$ = this.isAuthenticatedSubject.asObservable();

  constructor(
    private http: HttpClient, 
    private router: Router,
    private permissionService: PermissionService
  ) {
    this.verifyAuthentication().subscribe({
      next: (user) => {
        this.isAuthenticatedSubject.next(true);
        this.permissionService.setPermissions(user.permissions, user.roles);
      },
      error: () => {
        this.isAuthenticatedSubject.next(false);
        this.permissionService.clearPermissions();
      }
    });
  }

  getCsrfToken(): Observable<any> {
    return this.http.get(`${this.apiUrl}/sanctum/csrf-cookie`, { 
      withCredentials: true 
    }).pipe(
      catchError(error => throwError(() => error))
    );
  }

  login(credentials: LoginCredentials): Observable<any> {
    return this.getCsrfToken().pipe(
      switchMap(() =>
        this.http.post(`${this.apiUrl}/login`, credentials, { 
          withCredentials: true 
        })
      ),
      switchMap(() => this.getCurrentUser()),
      tap((user) => {
        this.isAuthenticatedSubject.next(true);
        this.permissionService.setPermissions(user.permissions, user.roles);
      }),
      catchError((error: HttpErrorResponse) => {
        this.isAuthenticatedSubject.next(false);
        this.permissionService.clearPermissions();
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
        this.permissionService.clearPermissions();
        this.router.navigate(['/login']);
      }),
      catchError(error => {
        this.isAuthenticatedSubject.next(false);
        this.permissionService.clearPermissions();
        return throwError(() => error);
      })
    );
  }

  verifyAuthentication(): Observable<User> {
    return this.http.get<User>(`${this.apiUrl}/api/user`, { 
      withCredentials: true 
    }).pipe(
      tap((user) => {
        this.isAuthenticatedSubject.next(true);
        this.permissionService.setPermissions(user.permissions, user.roles);
      }),
      catchError(() => {
        this.isAuthenticatedSubject.next(false);
        this.permissionService.clearPermissions();
        return throwError(() => new Error('Not authenticated'));
      })
    );
  }

  getCurrentUser(): Observable<User> {
    return this.http.get<User>(`${this.apiUrl}/api/user`, {
      withCredentials: true
    });
  }

  /**
   * Recarga los permisos del usuario
   */
  refreshPermissions(): Observable<void> {
    return this.permissionService.loadUserPermissions().pipe(
      tap(data => {
        this.permissionService.setPermissions(data.permissions, data.roles);
      }),
      switchMap(() => [])
    );
  }
}