import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../services/AuthService/auth';
import { CommonModule } from '@angular/common';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { environment } from '../../../../environments/environment'; // ajusta la ruta si tu estructura difiere

@Component({
  selector: 'app-login',
  templateUrl: './login.html',
  styleUrls: ['./login.css'],
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule]
})
export class Login implements OnInit {
  loginForm: FormGroup;
  isLoading = false;
  errorMessage = '';

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router,
    private http: HttpClient
  ) {
    this.loginForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(6)]]
    });
  }

  ngOnInit(): void {
    // Al cargar la vista de login: prepara cookies CSRF/sesión para evitar 419 en el POST /login
    this.http.get(`${environment.BACKEND_URL}/sanctum/csrf-cookie`, { withCredentials: true })
      .subscribe({ next: () => {}, error: () => {} });
  }

  onLogin(): void {
    if (this.loginForm.valid) {
      this.isLoading = true;
      this.errorMessage = '';

      const formData = this.loginForm.value;

      this.authService.login(formData).subscribe({
        next: () => {
          this.router.navigate(['/private']);
        },
        error: (err: HttpErrorResponse) => {
          this.isLoading = false;

          if (err.status === 422) {
            this.errorMessage = 'Datos de login inválidos. Verifica tu email y contraseña.';
          } else if (err.status === 401) {
            this.errorMessage = 'Credenciales incorrectas.';
          } else if (err.status === 500) {
            this.errorMessage = 'Error interno del servidor. Intenta de nuevo más tarde.';
          } else if (err.status === 0) {
            this.errorMessage = 'No se pudo conectar al servidor.';
          } else {
            this.errorMessage = 'Error al iniciar sesión. Por favor, intenta de nuevo.';
          }
        },
        complete: () => {
          this.isLoading = false;
        }
      });
    } else {
      this.markFormGroupTouched();
    }
  }

  private markFormGroupTouched(): void {
    Object.keys(this.loginForm.controls).forEach(key => {
      this.loginForm.get(key)?.markAsTouched();
    });
  }

  getFieldError(fieldName: string): string {
    const field = this.loginForm.get(fieldName);
    if (field?.errors && field.touched) {
      if (field.errors['required']) return `${fieldName} es requerido`;
      if (field.errors['email']) return 'Email no válido';
      if (field.errors['minlength']) return `${fieldName} debe tener al menos ${field.errors['minlength'].requiredLength} caracteres`;
    }
    return '';
  }
}
