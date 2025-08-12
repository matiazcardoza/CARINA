// src/app/http-request.interceptor.ts
import { HttpInterceptorFn } from '@angular/common/http';
import { catchError, throwError } from 'rxjs';
import { inject } from '@angular/core';
import { Router } from '@angular/router';

export const httpRequestInterceptor: HttpInterceptorFn = (req, next) => {
    const router = inject(Router);

    return next(req).pipe(
        catchError((error) => {
            if (error.status === 401) {
                // Redirige al login si hay un error 401
                localStorage.removeItem('user'); // O limpia cualquier dato de usuario
                router.navigate(['/login']);
            }
            return throwError(() => error);
        })
    );
};