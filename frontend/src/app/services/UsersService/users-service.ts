import { Injectable, inject } from '@angular/core';
import { map, Observable } from 'rxjs';
import { UserElement} from '../../features/private/users/users';
import { RolesElement } from '../../features/private/users/form/users-form/users-form';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../environments/environment';

interface UserApiResponse {
  message: string;
  data: UserElement[];
}

interface RolesApiResponse {
  message: string;
  data: RolesElement[];
}

interface SingleApiResponse {
  message: string;
  data: UserElement;
}

export interface CreateUserData {
  id: number;
  num_doc: string;
  name: string;
  persona_name: string;
  last_name: string;
  email: string;
  roles: { id: number, name: string }[];
  role_names: string;
  state: number;
  created_at: string;
  updated_at: string;
}

interface UpdateUserRolesData {
  userId: number;
  roles: number[];
}

@Injectable({
  providedIn: 'root'
})
export class UsersService {
  private http = inject(HttpClient);
  private apiUrl = environment.BACKEND_URL;

  getUsers(): Observable<UserElement[]> {
    return this.http.get<UserApiResponse>(`${this.apiUrl}/api/users`, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  searchPersonByDni(dni: string): Observable<any> {
    return this.http.get<UserApiResponse>(`${this.apiUrl}/api/users-consult/${dni}`, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  createUser(userData: CreateUserData): Observable<UserElement> {
    return this.http.post<SingleApiResponse>(`${this.apiUrl}/api/users-create`, userData, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  updateUser(userData: CreateUserData): Observable<UserElement> {
    return this.http.put<SingleApiResponse>(`${this.apiUrl}/api/users-update`, userData, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  deleteUser(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/api/users-delete/${id}`, {
      withCredentials: true
    });
  }

  getRoles(): Observable<RolesElement[]> {
    return this.http.get<RolesApiResponse>(`${this.apiUrl}/api/users-roles`, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  updateUserRoles(updateData: UpdateUserRolesData): Observable<UserElement> {
    return this.http.put<SingleApiResponse>(`${this.apiUrl}/api/users-update-roles`, updateData, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  importUsers(): Observable<any> {
    return this.http.post(`${this.apiUrl}/api/importUser`, {}, { 
      withCredentials: true 
    }).pipe(
      map(response => {
        return response;
      }),
    );
  }
  importControlador(): Observable<any> {
    return this.http.post(`${this.apiUrl}/api/importControlador`, {}, { 
      withCredentials: true 
    }).pipe(
      map(response => {
        return response;
      }),
    );
  }
}
