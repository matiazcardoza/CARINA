import { Component, OnInit, inject } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule, AbstractControl } from '@angular/forms';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatDialogRef } from '@angular/material/dialog';
import { CommonModule } from '@angular/common';
import { UsersService } from '../../../../services/UsersService/users-service';
import { ChangeDetectorRef } from '@angular/core';

// Validador personalizado para verificar que las contraseñas coincidan
function passwordMatchValidator(control: AbstractControl): { [key: string]: any } | null {
  const password = control.get('password');
  const confirmPassword = control.get('confirmPassword');
  
  if (!password || !confirmPassword) {
    return null;
  }
  
  return password.value === confirmPassword.value ? null : { 'passwordMismatch': true };
}

@Component({
  selector: 'app-profile-form',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatIconModule
  ],
  templateUrl: './profile-form.html',
  styleUrls: ['./profile-form.css']
})
export class ProfileForm implements OnInit {
  profileForm: FormGroup;
  hidePassword = true;
  hideConfirmPassword = true;
  isLoading = false;

  private fb = inject(FormBuilder);
  private dialogRef = inject(MatDialogRef<ProfileForm>);
  private usersService = inject(UsersService);
  private cdr = inject(ChangeDetectorRef);


  constructor() {
    this.profileForm = this.fb.group({
      password: ['', [
        Validators.required, 
        Validators.minLength(8),
        Validators.pattern(/^[a-zA-Z0-9]+$/)
      ]],
      confirmPassword: ['', [Validators.required]]
    }, { validators: passwordMatchValidator });
  }

  ngOnInit(): void {
    // Inicialización si es necesario
  }

  togglePasswordVisibility(): void {
    this.hidePassword = !this.hidePassword;
  }

  toggleConfirmPasswordVisibility(): void {
    this.hideConfirmPassword = !this.hideConfirmPassword;
  }

  onSubmit(): void {
    if (this.profileForm.valid) {
      this.isLoading = true;
      const formData = this.profileForm.value;
      
      // Eliminar confirmPassword antes de enviar
      const userData = {
        password: formData.password
      };
      
      console.log('Datos a enviar:', userData);
      
      // Simular llamada al servicio
      setTimeout(() => {
        this.usersService.changedPassword(userData)
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
        this.dialogRef.close(userData);
      }, 1000);
    } else {
      Object.keys(this.profileForm.controls).forEach(key => {
        this.profileForm.get(key)?.markAsTouched();
      });
    }
  }

  onCancel(): void {
    this.dialogRef.close(false);
  }
}