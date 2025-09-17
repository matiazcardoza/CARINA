import { Component, OnInit, signal, inject } from '@angular/core';
import { TableModule } from "primeng/table";
import { IconField } from "primeng/iconfield";
import { InputIcon } from "primeng/inputicon";

import { ShowUserDetailsModal } from './components/show-user-details-modal/show-user-details-modal';

import { HttpClient } from '@angular/common/http';

/* PrimeNG */
import { CommonModule, DatePipe } from '@angular/common';
import { InputTextModule } from 'primeng/inputtext';
import { ButtonModule } from 'primeng/button';
import { TagModule } from 'primeng/tag';
import { ChipModule } from 'primeng/chip';

/* Interfaces */
import { ApiResponse, RoleApi, UserApi, UserRow } from './interfaces/whm-user-management.interface';

@Component({
  selector: 'app-whm-user-management',
  imports: [TableModule, IconField, InputIcon, CommonModule, DatePipe, TableModule, InputTextModule, ButtonModule, TagModule, ChipModule, ShowUserDetailsModal],
  templateUrl: './whm-user-management.html',
  styleUrl: './whm-user-management.css'
})
export class WhmUserManagement {
  private http = inject(HttpClient);
  private readonly API = 'http://127.0.0.1:8000/api';

  loading = signal<boolean>(false);
  users = signal<UserRow[]>([]);
  isOpenModalShowUserDetails = signal<boolean>(false);
  selectedUser = signal<UserRow | null>(null); // <-- AÑADE ESTA LÍNEA
  pageSize = 10;
  ngOnInit(){
    this.loadUsers()
  }
  loadUsers(): void {
    this.loading.set(true);
    this.http.get<ApiResponse<UserApi[]>>(`${this.API}/users`, {
      // Si usas Sanctum/cookies:
      withCredentials: true
    }).subscribe({
      next: (res) => {
        const rows = (res.data ?? []).map((u) => {
          // Deduplicar roles (algunos te llegan repetidos)
          const uniqueById = new Map<number, RoleApi>();
          (u.roles ?? []).forEach(r => { if (!uniqueById.has(r.id)) uniqueById.set(r.id, r); });
          const roles = Array.from(uniqueById.values());
          const role_names = roles.map(r => r.name).join(', ');

          const persona =
            `${u.persona_name ?? ''} ${u.last_name ?? ''}`.trim() || 'N/A';

          return {
            id: u.id,
            num_doc: u.num_doc ?? 'N/A',
            usuario: u.name,
            persona,
            email: u.email,
            roles,
            role_names,
            state: Number(u.state ?? 1),
            created_at: u.created_at
          } as UserRow;
        });

        this.users.set(rows);
        this.loading.set(false);
      },
      error: () => this.loading.set(false)
    });
  }

  getStateSeverity(user: UserRow): 'success' | 'danger' {
    return user.state === 1 ? 'success' : 'danger';
  }

  onRefresh(): void {
    this.loadUsers();
  }

  // Acciones (wirea tus modales aquí)
  onView(user: UserRow):void {
     console.log(user)
     this.selectedUser.set(user); // <-- AÑADE ESTA LÍNEA
     this.isOpenModalShowUserDetails.set(true);
  }
  onEdit(user: UserRow) { 

  }
  onDelete(user: UserRow) {
     
  }

  closeModal(value:any){
    console.log("valor obtenido en el padrea:  ", value);
    this.isOpenModalShowUserDetails.set(value);
  }

}
