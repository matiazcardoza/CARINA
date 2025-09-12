import { Component, Inject, inject, OnInit, ViewEncapsulation } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogRef, MatDialogModule } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatIconModule } from '@angular/material/icon';
import { RolesService } from '../../../../../services/RolesService/roles-service';

export interface DialogData {
  isEdit: boolean;
  role: any;
}

@Component({
  selector: 'app-roles-form',
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatIconModule
  ],
  templateUrl: './roles-form.html',
  styleUrl: './roles-form.css',
  encapsulation: ViewEncapsulation.None
})
export class RolesForm implements OnInit {
  rolesForm: FormGroup;
  isLoading = false;
  
  private fb = inject(FormBuilder);
  private rolesService = inject(RolesService);

  constructor(
    public dialogRef: MatDialogRef<RolesForm>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData,
    private cdr: ChangeDetectorRef
  ) {
    this.rolesForm = this.fb.group({
      name: ['', [
        Validators.required, 
        Validators.maxLength(100),
        Validators.minLength(3)
      ]]
    });
  }

  ngOnInit() {
    if (this.data.isEdit && this.data.role) {
      this.rolesForm.patchValue({
        name: this.data.role.name
      });
    }
  }

  onSubmit(): void {
    if (this.rolesForm.valid) {
      this.isLoading = true;
      const formData = this.rolesForm.value;
      
      const roleData = { 
        ...formData,
        id: this.data.isEdit && this.data.role ? this.data.role.id : undefined
      };
      if (this.data.isEdit && this.data.role?.id) {
        setTimeout(() => {
          this.rolesService.updateRole(roleData)
            .subscribe({
              next: (updatedRole) => {
                this.isLoading = false;
                this.cdr.detectChanges();
                setTimeout(() => {
                  this.dialogRef.close(updatedRole);
                }, 100);
              },
              error: (error) => {
                this.isLoading = false;
                this.cdr.detectChanges();
                console.error('Error al actualizar rol:', error);
              }
            });
        }, 0);
      } else {
        setTimeout(() => {
          console.log('Crear rol con datos:', roleData);
          this.rolesService.createRole(roleData)
            .subscribe({
              next: (newRole) => {
                console.log('newRole', newRole);
                this.isLoading = false;
                this.cdr.detectChanges();
                setTimeout(() => {
                  this.dialogRef.close(newRole);
                }, 100);
              },
              error: (error) => {
                this.isLoading = false;
                this.cdr.detectChanges();
                console.error('Error al crear rol:', error);
              }
            });
        }, 0);
      }
    }
  }

  get title(): string {
    return this.data.isEdit ? 'Editar Rol' : 'Nuevo Rol';
  }

  get submitButtonText(): string {
    return this.data.isEdit ? 'Actualizar' : 'Crear';
  }

  onCancel() {
    this.dialogRef.close(false);
  }
}