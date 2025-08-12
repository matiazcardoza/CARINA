// src/app/features/public/register/register.ts
import { Component, OnInit } from '@angular/core';
import { AuthService } from '../../../services/auth';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';

@Component({
    selector: 'app-register',
    templateUrl: './register.html',
    styleUrl: './register.css',
    standalone: true,
    imports: [FormsModule]
})
export class Register implements OnInit {
    credentials = {
        name: '',
        email: '',
        password: '',
        password_confirmation: ''
    };

    constructor(private authService: AuthService, private router: Router) {}

    ngOnInit(): void {
        this.authService.getCsrfCookie().subscribe(); // Obtiene la cookie CSRF al cargar el componente
    }

    onRegister(): void {
      console.log('Registering with credentials:', this.credentials);
        this.authService.register(this.credentials).subscribe({
            next: (response) => {
                console.log('Registration successful', response);
                this.router.navigate(['/dashboard']); // Redirige al dashboard después del registro
            },
            error: (err) => {
                console.error('Registration failed', err);

            },
        });
    }
}