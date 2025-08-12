import { Component, OnInit } from '@angular/core';
import { AuthService } from '../../../services/auth';
import { Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';

@Component({
    selector: 'app-login',
    templateUrl: './login.html',
    styleUrl: './login.css',
    standalone: true,
    imports: [FormsModule, RouterLink]
})
export class Login implements OnInit {
    credentials = {
        email: '',
        password: '',
    };

    constructor(private authService: AuthService, private router: Router) {}

    ngOnInit(): void {
        this.authService.getCsrfCookie().subscribe();
    }

    onLogin(): void {
        this.authService.login(this.credentials).subscribe({
            next: (response) => {
                console.log('Login successful', response);
                this.router.navigate(['/dashboard']);
            },
            error: (err) => {
                console.error('Login failed', err);
            },
        });
    }
}