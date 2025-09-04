// src/app/services/AuthService/auth-expiration.interceptor.ts
import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { environment } from '../../../environments/environment';
import { catchError, switchMap } from 'rxjs/operators';
import { from, throwError } from 'rxjs';

const API_BASE = environment.BACKEND_URL.replace(/\/+$/, ''); // sin slash al final
let isRedirecting401 = false; // evita redirecciones múltiples simultáneas

function isApiUrl(url: string): boolean {
  return url.startsWith(API_BASE);
}

function isAuthOrCsrfPath(url: string): boolean {
  // Endpoints que NO deben disparar navegación por 401/419
  // Ajusta si tus rutas cambian
  return url.startsWith(`${API_BASE}/sanctum/csrf-cookie`)
      || url.startsWith(`${API_BASE}/login`)
      || url.startsWith(`${API_BASE}/logout`);
}

export const authExpirationInterceptor: HttpInterceptorFn = (req, next) => {
  const router = inject(Router);

  return next(req).pipe(
    catchError((err: HttpErrorResponse) => {
      // --------- 419: renovar CSRF y reintentar UNA vez ----------
      if (
        err.status === 419 &&
        isApiUrl(req.url) &&
        !isAuthOrCsrfPath(req.url) &&
        !req.headers.has('X-CSRF-Retry')
      ) {
        return from(fetch(`${API_BASE}/sanctum/csrf-cookie`, {
          method: 'GET',
          credentials: 'include' // importante para cookies
        })).pipe(
          switchMap(() => {
            const retried = req.clone({ setHeaders: { 'X-CSRF-Retry': '1' } });
            return next(retried);
          }),
          catchError(e => throwError(() => e))
        );
      }

      // --------- 401: sesión expirada -> redirigir a /login SOLO una vez ----------
      if (
        err.status === 401 &&
        isApiUrl(req.url) &&
        !isAuthOrCsrfPath(req.url)
      ) {
        // Si YA estamos en login/registro, NO navegamos (evita loop)
        const here = router.url;
        const alreadyOnAuthPage = here.startsWith('/login') || here.startsWith('/register');

        if (!alreadyOnAuthPage && !isRedirecting401) {
          isRedirecting401 = true;
          // Navegación única; reseteamos el flag después de completar
          router.navigateByUrl('/login').finally(() => {
            // pequeño delay por si hay varias requests paralelas con 401
            setTimeout(() => (isRedirecting401 = false), 200);
          });
        }
      }

      return throwError(() => err);
    })
  );
};
