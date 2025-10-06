// src/app/guards/permission.guard.ts
import { Injectable } from '@angular/core';
import { CanActivate, ActivatedRouteSnapshot, RouterStateSnapshot, Router } from '@angular/router';
import { Observable, map, take } from 'rxjs';
import { PermissionService } from './permission';
import { AuthService } from './auth';

@Injectable({
  providedIn: 'root'
})
export class PermissionGuard implements CanActivate {
  constructor(
    private permissionService: PermissionService,
    private authService: AuthService,
    private router: Router
  ) {}

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<boolean> | Promise<boolean> | boolean {
    
    // Obtener configuración desde route.data
    const requiredPermissions = route.data['permissions'] as string[] | string;
    const requiredRoles = route.data['roles'] as string[] | string;
    const checkType = route.data['checkType'] as 'any' | 'all' || 'any';
    const redirectTo = route.data['redirectTo'] as string || '/private/home';

    return this.authService.isAuthenticated$.pipe(
      take(1),
      map(isAuthenticated => {
        if (!isAuthenticated) {
          this.router.navigate(['/login']);
          return false;
        }

        // Verificar permisos si están definidos
        if (requiredPermissions) {
          const hasPermission = this.checkPermissions(requiredPermissions, checkType);
          if (!hasPermission) {
            console.warn(`Acceso denegado: Faltan permisos`, requiredPermissions);
            this.router.navigate([redirectTo]);
            return false;
          }
        }

        // Verificar roles si están definidos
        if (requiredRoles) {
          const hasRole = this.checkRoles(requiredRoles, checkType);
          if (!hasRole) {
            console.warn(`Acceso denegado: Faltan roles`, requiredRoles);
            this.router.navigate([redirectTo]);
            return false;
          }
        }

        // Si no se definieron permisos ni roles, permitir acceso
        if (!requiredPermissions && !requiredRoles) {
          console.warn('Ruta sin configuración de permisos/roles definida');
          return true;
        }

        return true;
      })
    );
  }

  private checkPermissions(permissions: string[] | string, checkType: 'any' | 'all'): boolean {
    if (typeof permissions === 'string') {
      return this.permissionService.hasPermission(permissions);
    }

    return checkType === 'all' 
      ? this.permissionService.hasAllPermissions(permissions)
      : this.permissionService.hasAnyPermission(permissions);
  }

  private checkRoles(roles: string[] | string, checkType: 'any' | 'all'): boolean {
    if (typeof roles === 'string') {
      return this.permissionService.hasRole(roles);
    }

    return checkType === 'all' 
      ? this.permissionService.hasAllRoles(roles)
      : this.permissionService.hasAnyRole(roles);
  }
}