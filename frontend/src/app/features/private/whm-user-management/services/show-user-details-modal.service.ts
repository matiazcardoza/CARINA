// import { Injectable } from '@angular/core';
// show-user-details-modal.service.ts
import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../../environments/environment';

export type ObraLite = { id:number; nombre:string; codigo:string };
export type RoleLite = { id:number; name:string; guard_name:string };
export type UserObraRow = { obra: ObraLite; roles: string[] };

@Injectable({ providedIn: 'root' })
@Injectable({
  providedIn: 'root'
})
export class ShowUserDetailsModalService {
  private apiUrl = environment.BACKEND_URL;
  private http = inject(HttpClient);
  // private readonly API = `http://127.0.0.1:8000/api/admin`;
  private readonly API = `${this.apiUrl}/api/admin`;

  getUserObras(userId: number) {
    return this.http.get<UserObraRow[]>(`${this.API}/users/${userId}/obras`, { withCredentials: true });
  }

  getCatalogObras() {
    return this.http.get<ObraLite[]>(`${this.API}/obras`, { withCredentials: true });
  }

  getCatalogRoles() {
    return this.http.get<RoleLite[]>(`${this.API}/roles`, { withCredentials: true });
  }

  addObra(userId: number, obraId: number, roles: string[] = []) {
    return this.http.post(`${this.API}/users/${userId}/obras`, { obra_id: obraId, roles }, { withCredentials: true });
  }

  removeObra(userId: number, obraId: number) {
    return this.http.delete(`${this.API}/users/${userId}/obras/${obraId}`, { withCredentials: true });
  }

  syncRoles(userId: number, obraId: number, roles: string[]) {
    return this.http.put(`${this.API}/users/${userId}/obras/${obraId}/roles`, { roles }, { withCredentials: true });
  }
}
