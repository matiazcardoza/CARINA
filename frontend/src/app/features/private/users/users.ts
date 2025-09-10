import { AfterViewInit, Component, ViewChild, OnInit, inject, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTableDataSource, MatTableModule } from '@angular/material/table';
import { MatPaginator, MatPaginatorModule } from '@angular/material/paginator';
import { MatDialog } from '@angular/material/dialog';
import { MatTooltipModule } from '@angular/material/tooltip';
import { UsersService } from '../../../services/UsersService/users-service';
import { UsersForm } from './form/users-form/users-form';

export interface UserElement {
  id: number;
  num_doc: string;
  persona_name: string;
  last_name: string;
  email: string;
  role_name: string;
  permissions: string[];
  state: number;
  created_at: string;
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
    MatTooltipModule
  ],
  templateUrl: './users.html',
  styleUrl: './users.css'
})
export class Users implements AfterViewInit, OnInit {
  
  displayedColumns: string[] = ['id', 'persona_name', 'email', 'role', 'state', 'created_at', 'actions'];
  dataSource = new MatTableDataSource<UserElement>([]);

  // private userService = inject(UserService);
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
          next: (data) => {
            console.log(data);
            this.dataSource.data = data;
            this.isLoading = false;
            this.cdr.detectChanges();
          },
          error: (error) => {
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
        mechanicalEquipment: null
      }
    });
      
    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.reloadData();
      }
    });
  }
  
  openEditDialog(user: UserElement) {
    console.log('Abrir diálogo para editar usuario:', user);
    // const dialogRef = this.dialog.open(UserFormComponent, {
    //   width: '700px',
    //   data: { 
    //     isEdit: true,
    //     user: user
    //   }
    // });
      
    // dialogRef.afterClosed().subscribe(result => {
    //   if (result) {
    //     this.reloadData();
    //   }
    // });
  }
  
  manageUserPermissions(user: UserElement) {
    console.log('Gestionar permisos para:', user);
    // Aquí irá la lógica para gestionar permisos usando Spatie
  }
  
  deleteUser(id: number) {
    if (confirm('¿Estás seguro de que deseas eliminar este usuario?')) {
      Promise.resolve().then(() => {
        this.isLoading = true;
        this.cdr.detectChanges();
        
        // Simular eliminación
        setTimeout(() => {
          this.dataSource.data = this.dataSource.data.filter(user => user.id !== id);
          this.isLoading = false;
          this.cdr.detectChanges();
        }, 1000);
        
        // En el futuro, reemplazar con:
        // this.userService.deleteUser(id)
        //   .subscribe({
        //     next: () => {
        //       this.isLoading = false;
        //       this.cdr.detectChanges();
        //       this.reloadData();
        //     },
        //     error: (error) => {
        //       this.isLoading = false;
        //       this.error = 'Error al eliminar el usuario. Por favor, intenta nuevamente.';
        //       this.cdr.detectChanges();
        //     }
        //   });
      });
    }
  }
  
  
  getStatusClass(state: string | number): string {
    const statusNum = Number(state);
    switch (statusNum) {
      case 1:
        // activo
        return 'status-active';
      case 2:
        // suspendido
        return 'status-suspended';
      case 3:
        // inactivo
        return 'status-inactive';
      default:
        return 'status-unknown';
    }
  }
  
  getRoleBadgeClass(role_name: string): string {
    switch (role_name.toLowerCase()) {
      case 'super administrador':
        return 'role-admin';
      case 'supervisor':
        return 'role-supervisor';
      case 'técnico':
        return 'role-technician';
      case 'operador':
        return 'role-operator';
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
}