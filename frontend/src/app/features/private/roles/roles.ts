import { AfterViewInit, Component, ViewChild, OnInit, inject, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTableDataSource, MatTableModule } from '@angular/material/table';
import { MatPaginator, MatPaginatorModule } from '@angular/material/paginator';
import { MatDialog } from '@angular/material/dialog';
import { MatTooltipModule } from '@angular/material/tooltip';
import { MatChipsModule } from '@angular/material/chips';
import { RolesService } from '../../../services/RolesService/roles-service';
import { RolesForm } from './form/roles-form/roles-form';
import { RolesPermissions } from './form/roles-permissions/roles-permissions';

export interface RoleElement {
  id: number;
  name: string;
  permissions_count?: number;
  users_count?: number;
  created_at: string;
  updated_at: string;
}

@Component({
  selector: 'app-roles',
  standalone: true,
  imports: [
    CommonModule,
    MatButtonModule,
    MatIconModule,
    MatTableModule,
    MatPaginatorModule,
    MatTooltipModule,
    MatChipsModule
  ],
  templateUrl: './roles.html',
  styleUrl: './roles.css'
})
export class Roles implements AfterViewInit, OnInit {
  
  displayedColumns: string[] = ['id', 'name', 'users_count', 'created_at', 'actions'];
  dataSource = new MatTableDataSource<RoleElement>([]);

  private dialog = inject(MatDialog);
  
  // Estado de carga inicial
  isLoading = false; 
  error: string | null = null;
  
  @ViewChild(MatPaginator) paginator!: MatPaginator;
  
  constructor(
    private rolesService: RolesService,
    private cdr: ChangeDetectorRef
  ) {}
  
  ngOnInit() {
    this.isLoading = false;
    this.error = null;
    this.cdr.detectChanges();
    
    Promise.resolve().then(() => {
      this.loadRolesData();
    });
  }
  
  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
  }
  
  loadRolesData(): void {
    Promise.resolve().then(() => {
      this.isLoading = true;
      this.error = null;
      this.cdr.detectChanges();
      
      this.rolesService.getRoles()
        .subscribe({
          next: (response) => {
            console.log('Roles data:', response);
            this.dataSource.data = response;
            this.isLoading = false;
            this.cdr.detectChanges();
          },
          error: (error) => {
            console.error('Error loading roles:', error);
            this.error = 'Error al cargar los datos. Por favor, intenta nuevamente.';
            this.isLoading = false;
            this.cdr.detectChanges();
          }
        });
    });
  }

  reloadData() {
    Promise.resolve().then(() => this.loadRolesData());
  }
  
  openCreateDialog() {
    const dialogRef = this.dialog.open(RolesForm, {
      width: '600px',
      data: { 
        isEdit: false,
        role: null
      }
    });
      
    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.reloadData();
      }
    });
  }
  
  openEditDialog(role: RoleElement) {
    const dialogRef = this.dialog.open(RolesForm, {
      width: '600px',
      data: { 
        isEdit: true,
        role: role
      }
    });
      
    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.reloadData();
      }
    });
  }
  
  manageRolePermissions(role: RoleElement) {
    const dialogRef = this.dialog.open(RolesPermissions, {
      width: '1000px',
      maxWidth: '100vw',
      data: { 
        role: role
      }
    });
      
    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.reloadData();
      }
    });
  }
  
  deleteRole(id: number) {
    if (confirm('¿Estás seguro de que deseas eliminar este rol?')) {
       Promise.resolve().then(() => {
        this.isLoading = true;
        this.cdr.detectChanges();
        
        this.rolesService.deleteRole(id)
          .subscribe({
            next: () => {
              this.isLoading = false;
              this.reloadData();
            },
            error: (error) => {
              this.isLoading = false;
              this.error = 'Error al eliminar el usuario. Por favor, intenta nuevamente.';
              this.cdr.detectChanges();
            }
          });
      });
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

  // Método para obtener el ícono según el tipo de rol
  getRoleIcon(roleName: string): string {
    switch (roleName.toLowerCase()) {
      case 'super administrador':
        return 'admin_panel_settings';
      case 'supervisor':
        return 'supervisor_account';
      case 'técnico':
        return 'build';
      case 'operador':
        return 'person';
      case 'controlador':
        return 'security';
      case 'residente':
        return 'home';
      default:
        return 'badge';
    }
  }

  // Método para verificar si un rol se puede eliminar (no tiene usuarios asignados)
  canDeleteRole(role: RoleElement): boolean {
    return role.users_count === 0;
  }

  // Método para obtener el tooltip de eliminación
  getDeleteTooltip(role: RoleElement): string {
    if (this.canDeleteRole(role)) {
      return 'Eliminar rol';
    }
    return `No se puede eliminar: ${role.users_count} usuario(s) asignado(s)`;
  }
}