import { Injectable, inject } from '@angular/core';
import { map, Observable } from 'rxjs';
import { RoleElement } from '../../features/private/roles/roles';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../environments/environment';

interface RolesApiResponse {
    message: string;
    data: RoleElement[];
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
}
