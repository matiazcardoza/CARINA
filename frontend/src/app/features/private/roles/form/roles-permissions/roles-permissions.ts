import { Component, Inject, inject, OnInit, ViewEncapsulation } from '@angular/core';
import { FormBuilder, FormGroup, FormArray, ReactiveFormsModule } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogRef, MatDialogModule } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatIconModule } from '@angular/material/icon';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatCardModule } from '@angular/material/card';
import { MatDividerModule } from '@angular/material/divider';
import { RolesService, ModulePermissions, Permission } from '../../../../../services/RolesService/roles-service';
import { MatSnackBar } from '@angular/material/snack-bar';

export interface DialogData {
  role: any;
}

@Component({
  selector: 'app-roles-permissions',
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatIconModule,
    MatCheckboxModule,
    MatCardModule,
    MatDividerModule
  ],
  templateUrl: './roles-permissions.html',
  styleUrl: './roles-permissions.css',
  encapsulation: ViewEncapsulation.None
})
export class RolesPermissions implements OnInit {
  permissionsForm: FormGroup;
  isLoading = false;
  isSaving = false;
  
  modulePermissions: ModulePermissions[] = [];
  
  private fb = inject(FormBuilder);
  private rolesService = inject(RolesService);
  private snackBar = inject(MatSnackBar);

  constructor(
    public dialogRef: MatDialogRef<RolesPermissions>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData,
    private cdr: ChangeDetectorRef
  ) {
    this.permissionsForm = this.fb.group({
      modules: this.fb.array([])
    });
  }

  get modulesFormArray(): FormArray {
    return this.permissionsForm.get('modules') as FormArray;
  }

  ngOnInit() {
    this.loadPermissionsData();
  }

  private loadPermissionsData(): void {
    this.isLoading = true;
    
    // Cargar permisos del rol desde el backend
    this.rolesService.getRolePermissions(this.data.role.id).subscribe({
      next: (response) => {
        this.modulePermissions = response.modulePermissions;
        this.initializeForm();
        this.loadCurrentPermissions(response.currentPermissions);
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (error) => {
        console.error('Error cargando permisos:', error);
        this.snackBar.open('Error al cargar los permisos', 'Cerrar', {
          duration: 3000,
          panelClass: ['error-snackbar']
        });
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  private initializeForm(): void {
    const modulesArray = this.fb.array<FormGroup>([]);
    
    this.modulePermissions.forEach(moduleData => {
      const permissionsArray = this.fb.array([]);
      
      moduleData.permissions.forEach(() => {
        permissionsArray.push(this.fb.control(false));
      });
      
      const moduleGroup = this.fb.group({
        module: [moduleData.module],
        permissions: permissionsArray
      });
      
      modulesArray.push(moduleGroup);
    });
    
    this.permissionsForm.setControl('modules', modulesArray);
  }

  private loadCurrentPermissions(currentPermissions: string[]): void {
    this.modulePermissions.forEach((moduleData, moduleIndex) => {
      const moduleGroup = this.modulesFormArray.at(moduleIndex) as FormGroup;
      const permissionsArray = moduleGroup.get('permissions') as FormArray;
      
      moduleData.permissions.forEach((permission, permIndex) => {
        const hasPermission = currentPermissions.includes(permission.name);
        permissionsArray.at(permIndex).setValue(hasPermission);
      });
    });
  }

  onModuleCheckboxChange(moduleIndex: number, permissionIndex: number, event: any): void {
    const moduleGroup = this.modulesFormArray.at(moduleIndex) as FormGroup;
    const permissionsArray = moduleGroup.get('permissions') as FormArray;
    permissionsArray.at(permissionIndex).setValue(event.checked);
  }

  selectAllPermissions(moduleIndex: number): void {
    const moduleGroup = this.modulesFormArray.at(moduleIndex) as FormGroup;
    const permissionsArray = moduleGroup.get('permissions') as FormArray;
    
    permissionsArray.controls.forEach(control => {
      control.setValue(true);
    });
  }

  deselectAllPermissions(moduleIndex: number): void {
    const moduleGroup = this.modulesFormArray.at(moduleIndex) as FormGroup;
    const permissionsArray = moduleGroup.get('permissions') as FormArray;
    
    permissionsArray.controls.forEach(control => {
      control.setValue(false);
    });
  }

  isModuleFullySelected(moduleIndex: number): boolean {
    const moduleGroup = this.modulesFormArray.at(moduleIndex) as FormGroup;
    const permissionsArray = moduleGroup.get('permissions') as FormArray;
    
    return permissionsArray.controls.every(control => control.value === true);
  }

  isModulePartiallySelected(moduleIndex: number): boolean {
    const moduleGroup = this.modulesFormArray.at(moduleIndex) as FormGroup;
    const permissionsArray = moduleGroup.get('permissions') as FormArray;
    
    const selectedCount = permissionsArray.controls.filter(control => control.value === true).length;
    return selectedCount > 0 && selectedCount < permissionsArray.controls.length;
  }

  getSelectedPermissionsCount(moduleIndex: number): number {
    const moduleGroup = this.modulesFormArray.at(moduleIndex) as FormGroup;
    const permissionsArray = moduleGroup.get('permissions') as FormArray;
    
    return permissionsArray.controls.filter(control => control.value === true).length;
  }

  onSubmit(): void {
    this.isSaving = true;
    
    const selectedPermissions: string[] = [];
    
    this.modulePermissions.forEach((moduleData, moduleIndex) => {
      const moduleGroup = this.modulesFormArray.at(moduleIndex) as FormGroup;
      const permissionsArray = moduleGroup.get('permissions') as FormArray;
      
      moduleData.permissions.forEach((permission, permIndex) => {
        if (permissionsArray.at(permIndex).value) {
          selectedPermissions.push(permission.name);
        }
      });
    });

    this.rolesService.updateRolePermissions(this.data.role.id, selectedPermissions).subscribe({
      next: (response) => {
        console.log('Permisos actualizados:', response);
        this.snackBar.open('Permisos actualizados correctamente', 'Cerrar', {
          duration: 3000,
          panelClass: ['success-snackbar']
        });
        
        this.isSaving = false;
        this.cdr.detectChanges();
        
        setTimeout(() => {
          this.dialogRef.close(true);
        }, 100);
      },
      error: (error) => {
        console.error('Error actualizando permisos:', error);
        this.snackBar.open('Error al actualizar los permisos', 'Cerrar', {
          duration: 3000,
          panelClass: ['error-snackbar']
        });
        
        this.isSaving = false;
        this.cdr.detectChanges();
      }
    });
  }

  onCancel(): void {
    this.dialogRef.close(false);
  }

  getModuleIcon(module: string): string {
    return `module-icon-${module}`;
  }

  getModuleIconName(module: string): string {
    switch (module) {
      case 'dashboard':
        return 'dashboard';
      case 'work_log':
        return 'work_history';
      case 'equipo_mecanico':
        return 'build';
      case 'reportes':
        return 'assessment';
      default:
        return 'folder';
    }
  }
}