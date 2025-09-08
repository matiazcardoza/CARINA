import { CanActivateFn, Router } from '@angular/router';
import { inject } from '@angular/core';
import { AuthService } from './auth';
import { catchError, map } from 'rxjs/operators';
import { of } from 'rxjs';

export const authGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  return authService.verifyAuthentication().pipe(
    map(() => true),
    catchError(() => {
      console.log('Acceso denegado. Redirigiendo a la página de login.');
      router.navigate(['/login']);
      return of(false);
    })
  );
};
