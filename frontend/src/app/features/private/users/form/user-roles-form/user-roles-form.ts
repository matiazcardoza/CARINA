import { Component, Inject, inject, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule, AbstractControl, FormArray } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogRef, MatDialogModule } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatButtonModule } from '@angular/material/button';
import { ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatIconModule } from '@angular/material/icon';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { UsersService } from '../../../../../services/UsersService/users-service';

export interface UserRolesDialogData {
  user: {
    id: number;
    name: string;
    email: string;
    roles?: any[];
    role_id?: number;
  };
}

export interface RolesElement {
  id: number;
  label: string;
  name?: string;
}

function rolesValidator(control: AbstractControl): { [key: string]: any } | null {
  const roles = control as FormArray;
  const hasSelectedRole = roles.controls.some(control => control.value === true);
  return hasSelectedRole ? null : { 'noRoleSelected': true };
}

@Component({
  selector: 'app-user-roles-form',
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatButtonModule,
    MatIconModule,
    MatCheckboxModule
  ],
  templateUrl: './user-roles-form.html',
  styleUrl: './user-roles-form.css'
})
export class UserRolesForm implements OnInit {
  rolesForm: FormGroup;
  isLoading = false;
  isLoadingRoles = false;
  roleOptions: RolesElement[] = [];

  private fb = inject(FormBuilder);
  private usersService = inject(UsersService);

  constructor(
    public dialogRef: MatDialogRef<UserRolesForm>,
    @Inject(MAT_DIALOG_DATA) public data: UserRolesDialogData,
    private cdr: ChangeDetectorRef
  ) {
    this.rolesForm = this.fb.group({
      roles: this.fb.array([])
    });
  }

  get rolesFormArray(): FormArray {
    return this.rolesForm.get('roles') as FormArray;
  }

  private initializeRolesFormArray(): void {
    const rolesArray = this.fb.array([]);
    
    this.roleOptions.forEach(() => {
      rolesArray.push(this.fb.control(false));
    });
    
    this.rolesForm.setControl('roles', rolesArray);
    rolesArray.setValidators(rolesValidator);
  }

  private loadRoles(): void {
    this.isLoadingRoles = true;
    
    this.usersService.getRoles().subscribe({
      next: (roles: RolesElement[]) => {
        this.roleOptions = roles.map(role => ({
          id: role.id,
          label: role.label || role.name || `Rol ${role.id}`
        }));
        
        this.initializeRolesFormArray();
        
        // Establecer roles actuales del usuario
        if (this.data.user) {
          const userRolesIds = Array.isArray(this.data.user.roles)
            ? this.data.user.roles.map((r: any) => r.id)
            : [this.data.user.role_id];
          
          this.setSelectedRoles(userRolesIds);
        }
        
        this.isLoadingRoles = false;
        this.cdr.detectChanges();
      },
      error: (error) => {
        console.error('Error al cargar roles:', error);
        this.isLoadingRoles = false;
        this.cdr.detectChanges();
      }
    });
  }

  private setSelectedRoles(userRoles: number[]): void {
    const rolesArray = this.rolesFormArray;
    
    this.roleOptions.forEach((role, index) => {
      const isSelected = userRoles.includes(role.id);
      rolesArray.at(index).setValue(isSelected);
    });
    
    rolesArray.updateValueAndValidity();
  }

  onRoleChange(index: number, event: any): void {
    const rolesArray = this.rolesFormArray;
    rolesArray.at(index).setValue(event.checked);
    rolesArray.updateValueAndValidity();
  }

  getSelectedRoleIds(): number[] {
    return this.rolesFormArray.controls
      .map((control, index) => control.value ? this.roleOptions[index].id : null)
      .filter(id => id !== null) as number[];
  }

  ngOnInit() {
    this.loadRoles();
  }

  onSubmit(): void {
    if (this.rolesForm.valid) {
      this.isLoading = true;
      
      const selectedRoleIds = this.getSelectedRoleIds();
      const updateData = {
        userId: this.data.user.id,
        roles: selectedRoleIds
      };

      // Llamada al servicio para actualizar roles
      this.usersService.updateUserRoles(updateData).subscribe({
        next: (updatedUser) => {
          this.isLoading = false;
          this.cdr.detectChanges();
          this.dialogRef.close(updatedUser);
        },
        error: (error) => {
          this.isLoading = false;
          this.cdr.detectChanges();
          console.error('Error al actualizar roles:', error);
        }
      });
    }
  }

  onCancel() {
    this.dialogRef.close(false);
  }

  hasChanges(): boolean {
    const currentRoles = this.getSelectedRoleIds();
    const originalRoles = Array.isArray(this.data.user.roles)
      ? this.data.user.roles.map((r: any) => r.id)
      : [this.data.user.role_id];
    
    return JSON.stringify(currentRoles.sort()) !== JSON.stringify(originalRoles.sort());
  }
}