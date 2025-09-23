import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface UserPermissions {
  permissions: string[];
  roles: string[];
}

@Injectable({
  providedIn: 'root'
})
export class PermissionService {
  private apiUrl = environment.BACKEND_URL;
  private permissionsSubject = new BehaviorSubject<string[]>([]);
  private rolesSubject = new BehaviorSubject<string[]>([]);

  permissions$ = this.permissionsSubject.asObservable();
  roles$ = this.rolesSubject.asObservable();

  constructor(private http: HttpClient) {}

  /**
   * Carga los permisos del usuario desde el backend
   */
  loadUserPermissions(): Observable<UserPermissions> {
    return this.http.get<UserPermissions>(`${this.apiUrl}/api/user/permissions`, {
      withCredentials: true
    });
  }

  /**
   * Actualiza los permisos en el servicio
   */
  setPermissions(permissions: string[], roles: string[]): void {
    this.permissionsSubject.next(permissions);
    this.rolesSubject.next(roles);
  }

  /**
   * Verifica si el usuario tiene un permiso específico
   */
  hasPermission(permission: string): boolean {
    return this.permissionsSubject.value.includes(permission);
  }

  /**
   * Verifica si el usuario tiene alguno de los permisos especificados
   */
  hasAnyPermission(permissions: string[]): boolean {
    return permissions.some(permission => this.hasPermission(permission));
  }

  /**
   * Verifica si el usuario tiene todos los permisos especificados
   */
  hasAllPermissions(permissions: string[]): boolean {
    return permissions.every(permission => this.hasPermission(permission));
  }

  /**
   * Verifica si el usuario tiene un rol específico
   */
  hasRole(role: string): boolean {
    return this.rolesSubject.value.includes(role);
  }

  /**
   * Verifica si el usuario tiene alguno de los roles especificados
   */
  hasAnyRole(roles: string[]): boolean {
    return roles.some(role => this.hasRole(role));
  }

  /**
   * Verifica si el usuario tiene todos los roles especificados
   */
  hasAllRoles(roles: string[]): boolean {
    return roles.every(role => this.hasRole(role));
  }

  /**
   * Obtiene los permisos actuales
   */
  getCurrentPermissions(): string[] {
    return this.permissionsSubject.value;
  }

  /**
   * Obtiene los roles actuales
   */
  getCurrentRoles(): string[] {
    return this.rolesSubject.value;
  }

  /**
   * Limpia los permisos (útil al cerrar sesión)
   */
  clearPermissions(): void {
    this.permissionsSubject.next([]);
    this.rolesSubject.next([]);
  }
}