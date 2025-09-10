import { AfterViewInit, Component, ViewChild, OnInit, inject, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTableDataSource, MatTableModule } from '@angular/material/table';
import { MatPaginator, MatPaginatorModule } from '@angular/material/paginator';
import { MatDialog } from '@angular/material/dialog';
import { MatTooltipModule } from '@angular/material/tooltip';
import { MatChipsModule } from '@angular/material/chips';

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
      
      // Simulando una llamada a la API con datos falsos
      setTimeout(() => {
        try {
          const mockRoles: RoleElement[] = [
            {
              id: 1,
              name: 'Super Administrador',
              permissions_count: 25,
              users_count: 2,
              created_at: '2024-01-15T10:30:00Z',
              updated_at: '2024-01-15T10:30:00Z'
            },
            {
              id: 2,
              name: 'Supervisor',
              permissions_count: 18,
              users_count: 5,
              created_at: '2024-02-01T14:20:00Z',
              updated_at: '2024-02-01T14:20:00Z'
            },
            {
              id: 3,
              name: 'Técnico',
              permissions_count: 12,
              users_count: 8,
              created_at: '2024-02-10T09:15:00Z',
              updated_at: '2024-02-10T09:15:00Z'
            },
            {
              id: 4,
              name: 'Operador',
              permissions_count: 8,
              users_count: 15,
              created_at: '2024-02-15T16:45:00Z',
              updated_at: '2024-02-15T16:45:00Z'
            },
            {
              id: 5,
              name: 'Controlador',
              permissions_count: 10,
              users_count: 6,
              created_at: '2024-03-01T11:30:00Z',
              updated_at: '2024-03-01T11:30:00Z'
            },
            {
              id: 6,
              name: 'Residente',
              permissions_count: 3,
              users_count: 120,
              created_at: '2024-03-05T08:00:00Z',
              updated_at: '2024-03-05T08:00:00Z'
            }
          ];
          
          console.log('Roles data:', mockRoles);
          this.dataSource.data = mockRoles;
          this.isLoading = false;
          this.cdr.detectChanges();
        } catch (error) {
          console.error('Error loading roles:', error);
          this.error = 'Error al cargar los datos. Por favor, intenta nuevamente.';
          this.isLoading = false;
          this.cdr.detectChanges();
        }
      }, 1500); // Simular delay de red
    });
  }

  reloadData() {
    Promise.resolve().then(() => this.loadRolesData());
  }
  
  openCreateDialog() {
    console.log('Abrir diálogo para crear nuevo rol');
    // TODO: Implementar diálogo de creación
    // const dialogRef = this.dialog.open(RolesForm, {
    //   width: '600px',
    //   data: { 
    //     isEdit: false,
    //     role: null
    //   }
    // });
      
    // dialogRef.afterClosed().subscribe(result => {
    //   if (result) {
    //     this.reloadData();
    //   }
    // });
  }
  
  openEditDialog(role: RoleElement) {
    console.log('Editar rol:', role);
    // TODO: Implementar diálogo de edición
    // const dialogRef = this.dialog.open(RolesForm, {
    //   width: '600px',
    //   data: { 
    //     isEdit: true,
    //     role: role
    //   }
    // });
      
    // dialogRef.afterClosed().subscribe(result => {
    //   if (result) {
    //     this.reloadData();
    //   }
    // });
  }
  
  manageRolePermissions(role: RoleElement) {
    console.log('Gestionar permisos del rol:', role);
    // TODO: Implementar diálogo de gestión de permisos
    // const dialogRef = this.dialog.open(RolePermissionsForm, {
    //   width: '800px',
    //   data: { 
    //     role: role
    //   }
    // });
      
    // dialogRef.afterClosed().subscribe(result => {
    //   if (result) {
    //     this.reloadData();
    //   }
    // });
  }
  
  deleteRole(id: number) {
    if (confirm('¿Estás seguro de que deseas eliminar este rol?')) {
      Promise.resolve().then(() => {
        this.isLoading = true;
        this.cdr.detectChanges();
        
        // Simulando eliminación
        setTimeout(() => {
          try {
            console.log('Eliminando rol con ID:', id);
            // Remover del dataSource para simular eliminación
            this.dataSource.data = this.dataSource.data.filter(role => role.id !== id);
            this.isLoading = false;
            this.cdr.detectChanges();
          } catch (error) {
            console.error('Error deleting role:', error);
            this.isLoading = false;
            this.error = 'Error al eliminar el rol. Por favor, intenta nuevamente.';
            this.cdr.detectChanges();
          }
        }, 1000);
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