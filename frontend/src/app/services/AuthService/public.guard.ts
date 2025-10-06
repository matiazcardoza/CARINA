import { CanActivateFn, Router, UrlTree } from '@angular/router';
import { inject } from '@angular/core';
import { AuthService } from './auth';
import { catchError, map, switchMap, take } from 'rxjs/operators';
import { of, Observable } from 'rxjs';

export const publicGuard: CanActivateFn = (route, state): Observable<boolean | UrlTree> => {
  const authService = inject(AuthService);
  const router = inject(Router);

  return authService.verifyAuthentication().pipe(
    map(() => {
      return router.createUrlTree(['/dashboard']);
    }),
    catchError(() => {
      return of(true);
    })
  );
};