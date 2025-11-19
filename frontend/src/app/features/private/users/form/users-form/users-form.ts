import { Component, Inject, inject, OnInit, ViewEncapsulation } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule, AbstractControl, FormArray } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogRef, MatDialogModule } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { ChangeDetectorRef } from '@angular/core';
import { MatSelectModule } from '@angular/material/select';
import { CommonModule } from '@angular/common';
import { MatIconModule } from '@angular/material/icon';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { UsersService } from '../../../../../services/UsersService/users-service';

export interface DialogData {
  isEdit: boolean;
  user: any;
}

export interface RolesElement {
  id: number;
  label: string;
  name?: string;
}

function passwordMatchValidator(control: AbstractControl): { [key: string]: any } | null {
  const password = control.get('password');
  const confirmPassword = control.get('confirmPassword');
  
  if (!password || !confirmPassword) {
    return null;
  }
  
  return password.value === confirmPassword.value ? null : { 'passwordMismatch': true };
}

function rolesValidator(control: AbstractControl): { [key: string]: any } | null {
  const roles = control as FormArray;
  const hasSelectedRole = roles.controls.some(control => control.value === true);
  return hasSelectedRole ? null : { 'noRoleSelected': true };
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
    MatIconModule,
    MatCheckboxModule
  ],
  templateUrl: './users-form.html',
  styleUrl: './users-form.css',
  encapsulation: ViewEncapsulation.None
})
export class UsersForm implements OnInit {
  usersForm: FormGroup;
  isLoading = false;
  isSearchingDni = false;
  isLoadingRoles = false;
  hidePassword = true;
  hideConfirmPassword = true;

  roleOptions: RolesElement[] = [];

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
        Validators.pattern(/^[a-zA-Z0-9]+$/)
      ]],
      confirmPassword: ['', [Validators.required]],
      roles: this.fb.array([]),
      state: [1, Validators.required]
    }, { validators: passwordMatchValidator });
  }

  get rolesFormArray(): FormArray {
    return this.usersForm.get('roles') as FormArray;
  }

  private initializeRolesFormArray(): void {
    const rolesArray = this.fb.array([]);
    
    this.roleOptions.forEach(() => {
      rolesArray.push(this.fb.control(false));
    });
    
    this.usersForm.setControl('roles', rolesArray);
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
        
        if (this.data.isEdit && this.data.user) {
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
        username: this.data.user.name,
        email: this.data.user.email,
        state: this.data.user.state
      }, { emitEvent: false });
      
      this.usersForm.get('password')?.clearValidators();
      this.usersForm.get('password')?.setValidators([
        Validators.minLength(8),
        Validators.pattern(/^[a-zA-Z0-9]+$/)
      ]);
      this.usersForm.get('confirmPassword')?.clearValidators();
      this.usersForm.get('password')?.updateValueAndValidity();
      this.usersForm.get('confirmPassword')?.updateValueAndValidity();
    }
  }

  private setSelectedRoles(userRoles: number[]): void {
    const rolesArray = this.rolesFormArray;
    
    this.roleOptions.forEach((role, index) => {
      const isSelected = userRoles.includes(role.id);
      rolesArray.at(index).setValue(isSelected);
    });
    
    rolesArray.updateValueAndValidity();
  }

  onDniInput(event: any): void {
    const input = event.target;
    const value = input.value;
    
    const numericValue = value.replace(/[^0-9]/g, '');
    
    const limitedValue = numericValue.substring(0, 8);
    
    if (value !== limitedValue) {
      input.value = limitedValue;
      this.usersForm.get('num_doc')?.setValue(limitedValue);
    }
  }

  togglePasswordVisibility(): void {
    this.hidePassword = !this.hidePassword;
  }

  toggleConfirmPasswordVisibility(): void {
    this.hideConfirmPassword = !this.hideConfirmPassword;
  }

  searchPersonByDni(dni: string): void {
    this.isSearchingDni = true;
    this.cdr.detectChanges();

    this.usersService.searchPersonByDni(dni).subscribe({
      next: (personData) => {
        if (personData) {
          this.usersForm.patchValue({
            persona_name: personData.name || '',
            last_name: personData.last_name || ''
          });
          
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

  onSubmit(): void {
    if (this.usersForm.valid) {
      this.isLoading = true;
      const formData = this.usersForm.value;
      
      const userData = { 
        ...formData,
        roles: this.getSelectedRoleIds(),
        id: this.data.isEdit && this.data.user ? this.data.user.id : undefined
      };
      
      if (this.data.isEdit) {
        if (!userData.password || userData.password.trim() === '') {
          delete userData.password;
          delete userData.confirmPassword;
        }
      }
      
      delete userData.confirmPassword;

      if (this.data.isEdit && this.data.user?.id) {
        setTimeout(() => {
          console.log('Actualizar usuario con datos:', userData);
          this.usersService.updateUser(userData)
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
                console.error('Error al actualizar usuario:', error);
              }
            });
        }, 0);
      } else {
        setTimeout(() => {
          console.log('Crear usuario con datos:', userData);
          this.usersService.createUser(userData)
            .subscribe({
              next: (newUser) => {
                console.log('newUser', newUser);
                this.isLoading = false;
                this.cdr.detectChanges();
                setTimeout(() => {
                  this.dialogRef.close(newUser);
                }, 100);
              },
              error: (error) => {
                this.isLoading = false;
                this.cdr.detectChanges();
                console.error('Error al crear usuario:', error);
              }
            });
        }, 0);
      }
    }
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