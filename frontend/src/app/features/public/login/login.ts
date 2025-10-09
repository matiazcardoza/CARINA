import { Component, DestroyRef, inject, OnInit, ViewEncapsulation } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../services/AuthService/auth';
import { CommonModule } from '@angular/common';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { environment } from '../../../../environments/environment';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { finalize } from 'rxjs/operators';
import { MessageService } from 'primeng/api';
import { Toast } from 'primeng/toast';
import { parseHttpError } from '../../../shared/utils/parseHttpError';
@Component({
  selector: 'app-login',
  templateUrl: './login.html',
  styleUrls: ['./login.css'],
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, Toast],
  encapsulation: ViewEncapsulation.None,
  providers: [MessageService]
})
export class Login implements OnInit {
  loginForm: FormGroup;
  isLoading = false;
  errorMessage = '';
  private readonly destroyRef = inject(DestroyRef);
  private readonly messageService = inject(MessageService);

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router,
    private http: HttpClient
  ) {
    this.loginForm = this.fb.group({
      num_doc: ['', [Validators.required, Validators.minLength(8)]], // Cambiado de email
      password: ['', [Validators.required, Validators.minLength(6)]]
    });
  }

  ngOnInit(): void {
    // Al cargar la vista de login: prepara cookies CSRF/sesión para evitar 419 en el POST /login
    this.http.get(`${environment.BACKEND_URL}/sanctum/csrf-cookie`, { withCredentials: true })
      .subscribe({ next: () => {}, error: () => {} });
  }

  onLogin(): void {
    console.log("este es un mensjae")
    if (this.loginForm.valid) {
      this.isLoading = true;
      this.errorMessage = '';

      const formData = this.loginForm.value;

      this.authService.login(formData).pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(()=>{
          console.log("poner fin al loading");
          this.isLoading = false
        })
      ).subscribe({
        next: () => {
          this.router.navigate(['/dashboard']);
        },
        error: async (err: HttpErrorResponse) => {
            const p = await parseHttpError(err);
            this.messageService.add({ severity:p.severity, summary:p.title, detail:p.detail });
            // this.isLoading = false;

            if (err.status === 422) {
              this.errorMessage = 'Datos de login inválidos. Verifica tu número de documento y contraseña.';
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
        // complete: () => {
        //   this.isLoading = false;
        // }
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
      if (field.errors['required']) {
        return fieldName === 'num_doc' ? 'El número de documento es requerido' : 'La contraseña es requerida';
      }
      if (field.errors['minlength']) {
        const minLength = field.errors['minlength'].requiredLength;
        return fieldName === 'num_doc' 
          ? `El número de documento debe tener al menos ${minLength} caracteres`
          : `La contraseña debe tener al menos ${minLength} caracteres`;
      }
    }
    return '';
  }
}