import { Injectable, inject } from '@angular/core';
import { map, Observable } from 'rxjs';
import { RoleElement } from '../../features/private/roles/roles';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../environments/environment';

export interface ModulePermissions {
    module: string;
    moduleLabel: string;
    permissions: Permission[];
}

interface RolePermissionsResponse {
    message: string;
    data: {
        role: RoleElement;
        modulePermissions: ModulePermissions[];
        currentPermissions: string[];
    };
}

export interface Permission {
    id: number;
    name: string;
    label: string;
    module: string;
    guard_name: string;
    assigned?: boolean;
}

interface UpdatePermissionsResponse {
    message: string;
    data: {
        role: RoleElement;
        permissions: string[];
    };
}

interface RolesApiResponse {
    message: string;
    data: RoleElement[];
}

interface SingleApiResponse {
    message: string;
    data: RoleElement;
}

@Injectable({
  providedIn: 'root'
})
export class RolesService {

  private http = inject(HttpClient);
  private apiUrl = environment.BACKEND_URL;

  getRoles(): Observable<RoleElement[]> {
    return this.http.get<RolesApiResponse>(`${this.apiUrl}/api/roles`, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  createRole(roleData: RoleElement): Observable<RoleElement> {
    return this.http.post<SingleApiResponse>(`${this.apiUrl}/api/roles-create`, roleData, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  updateRole(roleData: RoleElement): Observable<RoleElement> {
    return this.http.put<SingleApiResponse>(`${this.apiUrl}/api/roles-update`, roleData, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  getRolePermissions(roleId: number): Observable<{
    role: RoleElement;
    modulePermissions: ModulePermissions[];
    currentPermissions: string[];
  }> {
    return this.http.get<RolePermissionsResponse>(`${this.apiUrl}/api/roles-permissions`, {
      params: { role_id: roleId.toString() },
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  updateRolePermissions(roleId: number, permissions: string[]): Observable<{
    role: RoleElement;
    permissions: string[];
  }> {
    return this.http.put<UpdatePermissionsResponse>(`${this.apiUrl}/api/roles-permissions`, {
      role_id: roleId,
      permissions: permissions
    }, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  deleteRole(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/api/roles-delete/${id}`, {
      withCredentials: true
    });
  }
}
