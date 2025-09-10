import { AfterViewInit, Component, ViewChild, OnInit, inject, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTableDataSource, MatTableModule } from '@angular/material/table';
import { MatPaginator, MatPaginatorModule } from '@angular/material/paginator';
import { MatDialog } from '@angular/material/dialog';
import { MatTooltipModule } from '@angular/material/tooltip';
import { MatChipsModule } from '@angular/material/chips';
import { UsersService } from '../../../services/UsersService/users-service';
import { UsersForm } from './form/users-form/users-form';
import { UserRolesForm } from './form/user-roles-form/user-roles-form';

export interface UserElement {
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

@Component({
  selector: 'app-users',
  standalone: true,
  imports: [
    CommonModule,
    MatButtonModule,
    MatIconModule,
    MatTableModule,
    MatPaginatorModule,
    MatTooltipModule,
    MatChipsModule // Para mostrar roles como chips
  ],
  templateUrl: './users.html',
  styleUrl: './users.css'
})
export class Users implements AfterViewInit, OnInit {
  
  displayedColumns: string[] = ['id', 'persona_name', 'email', 'roles', 'state', 'created_at', 'actions'];
  dataSource = new MatTableDataSource<UserElement>([]);

  private dialog = inject(MatDialog);
  
  // Estado de carga inicial
  isLoading = false; 
  error: string | null = null;
  
  @ViewChild(MatPaginator) paginator!: MatPaginator;
  
  constructor(
    private usersService: UsersService,
    private cdr: ChangeDetectorRef
  ) {}
  
  ngOnInit() {
    this.isLoading = false;
    this.error = null;
    this.cdr.detectChanges();
    
    Promise.resolve().then(() => {
      this.loadUsersData();
    });
  }
  
  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
  }
  
  loadUsersData(): void {
    Promise.resolve().then(() => {
      this.isLoading = true;
      this.error = null;
      this.cdr.detectChanges();
      
      this.usersService.getUsers()
        .subscribe({
          next: (response) => {
            console.log('Users data:', response);
            // Asumiendo que la respuesta viene en response.data
            this.dataSource.data = response;
            this.isLoading = false;
            this.cdr.detectChanges();
          },
          error: (error) => {
            console.error('Error loading users:', error);
            this.error = 'Error al cargar los datos. Por favor, intenta nuevamente.';
            this.isLoading = false;
            this.cdr.detectChanges();
          }
        });
    });
  }

  reloadData() {
    Promise.resolve().then(() => this.loadUsersData());
  }
  
  openCreateDialog() {
    const dialogRef = this.dialog.open(UsersForm, {
      width: '700px',
      data: { 
        isEdit: false,
        user: null
      }
    });
      
    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.reloadData();
      }
    });
  }
  
  openEditDialog(user: UserElement) {
    const dialogRef = this.dialog.open(UsersForm, {
      width: '700px',
      data: { 
        isEdit: true,
        user: user
      }
    });
      
    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.reloadData();
      }
    });
  }
  
  manageUserRoles(user: UserElement) {
    const dialogRef = this.dialog.open(UserRolesForm, {
      width: '700px',
      data: { 
        user: user
      }
    });
      
    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.reloadData();
      }
    });
  }
  
  deleteUser(id: number) {
    if (confirm('¿Estás seguro de que deseas eliminar este usuario?')) {
      Promise.resolve().then(() => {
        this.isLoading = true;
        this.cdr.detectChanges();
        
        this.usersService.deleteUser(id)
          .subscribe({
            next: () => {
              this.isLoading = false;
              this.reloadData();
              this.cdr.detectChanges();
            },
            error: (error) => {
              console.error('Error deleting user:', error);
              this.isLoading = false;
              this.error = 'Error al eliminar el usuario. Por favor, intenta nuevamente.';
              this.cdr.detectChanges();
            }
          });
      });
    }
  }
  
  getStatusClass(state: string | number): string {
    const statusNum = Number(state);
    switch (statusNum) {
      case 1:
        return 'status-active';
      case 2:
        return 'status-suspended';
      case 3:
        return 'status-inactive';
      default:
        return 'status-unknown';
    }
  }
  
  getStatusText(state: string | number): string {
    const statusNum = Number(state);
    switch (statusNum) {
      case 1:
        return 'Activo';
      case 2:
        return 'Suspendido';
      case 3:
        return 'Inactivo';
      default:
        return 'Desconocido';
    }
  }
  
  getStatusIcon(state: string | number): string {
    const statusNum = Number(state);
    switch (statusNum) {
      case 1:
        return 'check_circle';
      case 2:
        return 'pause_circle';
      case 3:
        return 'cancel';
      default:
        return 'help_outline';
    }
  }
  
  getRoleBadgeClass(roleName: string): string {
    switch (roleName.toLowerCase()) {
      case 'super administrador':
        return 'role-admin';
      case 'supervisor':
        return 'role-supervisor';
      case 'técnico':
        return 'role-technician';
      case 'operador':
        return 'role-operator';
      case 'controlador':
        return 'role-controller';
      case 'residente':
        return 'role-resident';
      default:
        return 'role-default';
    }
  }
  
  formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  // Método para obtener el nombre completo
  getFullName(user: UserElement): string {
    const personaName = user.persona_name || '';
    const lastName = user.last_name || '';
    return `${personaName} ${lastName}`.trim() || 'Sin nombre';
  }

  // Método para manejar roles vacíos
  getUserRoles(user: UserElement): { id: number, name: string }[] {
    return user.roles && user.roles.length > 0 ? user.roles : [];
  }

  hasRoles(user: UserElement): boolean {
    return user.roles && user.roles.length > 0;
  }
}