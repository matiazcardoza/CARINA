import { Component, Inject, inject, OnInit, ViewEncapsulation } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogRef, MatDialogModule } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { ChangeDetectorRef } from '@angular/core';
import { MatSelectModule } from '@angular/material/select';
import { CommonModule } from '@angular/common';
import { MatIconModule } from '@angular/material/icon';
import { UsersService } from '../../../../../services/UsersService/users-service';

export interface DialogData {
  isEdit: boolean;
  user: any;
}

@Component({
  selector: 'app-users-form',
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatSelectModule,
    MatIconModule
  ],
  templateUrl: './users-form.html',
  styleUrl: './users-form.css',
  encapsulation: ViewEncapsulation.None
})
export class UsersForm implements OnInit {
  usersForm: FormGroup;
  isLoading = false;
  isSearchingDni = false;

  roleOptions = [
    { value: 1, label: 'Super Administrador' },
    { value: 2, label: 'Supervisor' },
    { value: 3, label: 'Técnico' },
    { value: 4, label: 'Operador' }
  ];

  stateOptions = [
    { value: 1, label: 'Activo' },
    { value: 2, label: 'Suspendido' },
    { value: 3, label: 'Inactivo' }
  ];
  
  private fb = inject(FormBuilder);
  private usersService = inject(UsersService);

  constructor(
    public dialogRef: MatDialogRef<UsersForm>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData,
    private cdr: ChangeDetectorRef
  ) {
    this.usersForm = this.fb.group({
      num_doc: ['', [
        Validators.required, 
        Validators.pattern(/^\d{8}$/),
        Validators.minLength(8),
        Validators.maxLength(8)
      ]],
      persona_name: ['', [Validators.required, Validators.maxLength(100)]],
      last_name: ['', [Validators.required, Validators.maxLength(100)]],
      username: ['', [Validators.required, Validators.maxLength(50)]],
      email: ['', [Validators.required, Validators.email, Validators.maxLength(100)]],
      password: ['', [
        Validators.required, 
        Validators.minLength(8),
        Validators.pattern(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/)
      ]],
      role_id: ['', Validators.required],
      state: [1, Validators.required]
    });
  }
  
  ngOnInit() {
    // Escuchar cambios en el campo DNI
    this.usersForm.get('num_doc')?.valueChanges.subscribe(value => {
      if (value && value.length === 8 && /^\d{8}$/.test(value)) {
        this.searchPersonByDni(value);
      }
    });

    if (this.data.isEdit && this.data.user) {
      this.usersForm.patchValue({
        num_doc: this.data.user.num_doc,
        persona_name: this.data.user.persona_name,
        last_name: this.data.user.last_name,
        username: this.data.user.username,
        email: this.data.user.email,
        role_id: this.data.user.role_id,
        state: this.data.user.state
      });
      
      // En modo edición, quitar la validación requerida de la contraseña
      this.usersForm.get('password')?.clearValidators();
      this.usersForm.get('password')?.setValidators([
        Validators.minLength(8),
        Validators.pattern(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/)
      ]);
      this.usersForm.get('password')?.updateValueAndValidity();
    }
  }

  searchPersonByDni(dni: string): void {
    this.isSearchingDni = true;
    this.cdr.detectChanges();

    this.usersService.searchPersonByDni(dni).subscribe({
      next: (personData) => {
        if (personData) {
          // Rellenar automáticamente los campos de nombres y apellidos
          this.usersForm.patchValue({
            persona_name: personData.name || '',
            last_name: personData.last_name || ''
          });
          
          // Marcar los campos como tocados para mostrar validaciones
          this.usersForm.get('persona_name')?.markAsTouched();
          this.usersForm.get('last_name')?.markAsTouched();
        }
        this.isSearchingDni = false;
        this.cdr.detectChanges();
      },
      error: (error) => {
        console.log('No se encontraron datos para el DNI:', dni);
        this.isSearchingDni = false;
        this.cdr.detectChanges();
      }
    });
  }

  onSubmit(): void {/*
    if (this.usersForm.valid) {
      this.isLoading = true;
      const formData = { ...this.usersForm.value };
      
      // En modo edición, si no se ingresó contraseña, no la incluir en el payload
      if (this.data.isEdit && !formData.password) {
        delete formData.password;
      }

      if (this.data.isEdit && this.data.user?.id) {
        setTimeout(() => {
          this.usersService.updateUser(this.data.user.id, formData)
            .subscribe({
              next: (updatedUser) => {
                this.isLoading = false;
                this.cdr.detectChanges();
                setTimeout(() => {
                  this.dialogRef.close(updatedUser);
                }, 100);
              },
              error: (error) => {
                this.isLoading = false;
                this.cdr.detectChanges();
                console.error('Error al actualizar:', error);
              }
          });
        }, 0);
      } else {
        setTimeout(() => {
          this.usersService.createUser(formData)
            .subscribe({
              next: (newUser) => {
                this.isLoading = false;
                this.cdr.detectChanges();
                setTimeout(() => {
                  this.dialogRef.close(newUser);
                }, 100);
              },
              error: (error) => {
                this.isLoading = false;
                this.cdr.detectChanges();
                console.error('Error al crear:', error);
              }
            });
        }, 0);
      }
    }*/
  }

  get title(): string {
    return this.data.isEdit ? 'Editar Usuario' : 'Nuevo Usuario';
  }

  get submitButtonText(): string {
    return this.data.isEdit ? 'Actualizar' : 'Crear';
  }

  onCancel() {
    this.dialogRef.close(false);
  }
}