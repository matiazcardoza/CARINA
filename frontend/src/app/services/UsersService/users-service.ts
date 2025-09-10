import { Injectable, inject } from '@angular/core';
import { map, Observable } from 'rxjs';
import { UserElement} from '../../features/private/users/users';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../environments/environment';

interface UserApiResponse {
  message: string;
  data: UserElement[];
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
}
