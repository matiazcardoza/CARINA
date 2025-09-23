import { Directive, Input, OnInit, TemplateRef, ViewContainerRef, OnDestroy } from '@angular/core';
import { PermissionService } from '../../services/AuthService/permission';
import { Subject, takeUntil } from 'rxjs';

@Directive({
  selector: '[appHasPermission]',
  standalone: true
})
export class HasPermissionDirective implements OnInit, OnDestroy {
  private destroy$ = new Subject<void>();
  private permission: string | string[] = '';
  private checkType: 'any' | 'all' = 'any';

  constructor(
    private templateRef: TemplateRef<any>,
    private viewContainer: ViewContainerRef,
    private permissionService: PermissionService
  ) {}

  @Input() set appHasPermission(permission: string | string[]) {
    this.permission = permission;
    this.updateView();
  }

  @Input() set appHasPermissionCheck(type: 'any' | 'all') {
    this.checkType = type;
    this.updateView();
  }

  ngOnInit() {
    // Escuchar cambios en los permisos
    this.permissionService.permissions$
      .pipe(takeUntil(this.destroy$))
      .subscribe(() => {
        this.updateView();
      });
  }

  ngOnDestroy() {
    this.destroy$.next();
    this.destroy$.complete();
  }

  private updateView() {
    if (this.hasPermission()) {
      // Solo crear la vista si no existe ya
      if (this.viewContainer.length === 0) {
        this.viewContainer.createEmbeddedView(this.templateRef);
      }
    } else {
      this.viewContainer.clear();
    }
  }

  private hasPermission(): boolean {
    if (!this.permission) return false;

    if (Array.isArray(this.permission)) {
      return this.checkType === 'all' 
        ? this.permissionService.hasAllPermissions(this.permission)
        : this.permissionService.hasAnyPermission(this.permission);
    } else {
      return this.permissionService.hasPermission(this.permission);
    }
  }
}

@Directive({
  selector: '[appHasRole]',
  standalone: true
})
export class HasRoleDirective implements OnInit, OnDestroy {
  private destroy$ = new Subject<void>();
  private role: string | string[] = '';
  private checkType: 'any' | 'all' = 'any';

  constructor(
    private templateRef: TemplateRef<any>,
    private viewContainer: ViewContainerRef,
    private permissionService: PermissionService
  ) {}

  @Input() set appHasRole(role: string | string[]) {
    this.role = role;
    this.updateView();
  }

  @Input() set appHasRoleCheck(type: 'any' | 'all') {
    this.checkType = type;
    this.updateView();
  }

  ngOnInit() {
    // Escuchar cambios en los roles
    this.permissionService.roles$
      .pipe(takeUntil(this.destroy$))
      .subscribe(() => {
        this.updateView();
      });
  }

  ngOnDestroy() {
    this.destroy$.next();
    this.destroy$.complete();
  }

  private updateView() {
    if (this.hasRole()) {
      // Solo crear la vista si no existe ya
      if (this.viewContainer.length === 0) {
        this.viewContainer.createEmbeddedView(this.templateRef);
      }
    } else {
      this.viewContainer.clear();
    }
  }

  private hasRole(): boolean {
    if (!this.role) return false;

    if (Array.isArray(this.role)) {
      return this.checkType === 'all' 
        ? this.permissionService.hasAnyRole(this.role) // Ajustar si implementas hasAllRoles
        : this.permissionService.hasAnyRole(this.role);
    } else {
      return this.permissionService.hasRole(this.role);
    }
  }
}