import { CanActivateFn, Router } from '@angular/router';
import { inject } from '@angular/core';
import { AuthService } from './auth'; // Asegúrate de que la ruta sea correcta
import { map, take } from 'rxjs/operators';

export const authGuard: CanActivateFn = (route, state) => {
  // Inyectamos el servicio de autenticación y el router.
  const authService = inject(AuthService);
  const router = inject(Router);

  // Verificamos si el usuario está autenticado observando el BehaviorSubject.
  return authService.isAuthenticated$.pipe(
    take(1), // Tomamos solo el primer valor y completamos el observable.
    map(isAuthenticated => {
      if (isAuthenticated) {
        // Si está autenticado, permite la navegación.
        return true;
      } else {
        // Si no está autenticado, redirige al login y previene la navegación.
        router.navigate(['/login']);
        return false;
      }
    })
  );
};