import { Component, inject, output } from '@angular/core';
import { Toolbar } from 'primeng/toolbar';
import { AvatarModule } from 'primeng/avatar';
import { SharedModule } from 'primeng/api';
import { ButtonModule } from 'primeng/button';
import { InputTextModule } from 'primeng/inputtext';
import { IconField } from 'primeng/iconfield';
import { InputIcon } from 'primeng/inputicon';
import { MenuItem } from 'primeng/api';
import { MenuModule } from 'primeng/menu';
import { AuthService } from '../../services/AuthService/auth';
import { finalize } from 'rxjs';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
@Component({
  selector: 'app-navbar',
  imports: [
    Toolbar, 
    AvatarModule, 
    ButtonModule,

    AvatarModule,
    ButtonModule,
    InputTextModule,
    IconField,
    MenuModule
  ],
  templateUrl: './navbar.html',
  styleUrl: './navbar.css',
})
export class Navbar {
  private auth = inject(AuthService);
  sentOpenValue = output<boolean>();
  handleOpenSidebar(value: boolean){
    this.sentOpenValue.emit(true)
  }
  isAuthenticated$ = this.auth.isAuthenticated$;
  loading = false;
  errorMsg = '';
  profileItems: MenuItem[] = [
    { label: 'Mi perfil', icon: 'pi pi-user',      command: () => this.onGoProfile() },
    { label: 'Ajustes',   icon: 'pi pi-cog',       command: () => this.onGoSettings() },
    { separator: true },
    { label: 'Cerrar sesión', icon: 'pi pi-sign-out', command: () => this.onLogout() }
  ];

  // handleOpenSidebar(value: boolean) { this.sentOpenValue.emit(value); }

  // Conecta estas acciones a tu Router/servicios
  onGoProfile() {}
  onGoSettings() {}
  onLogout() {
    console.log("sesión cerrada");
        if (this.loading) return;
    this.loading = true;
    this.errorMsg = '';

    this.auth.logout()
      .pipe(
        finalize(() => (this.loading = false)),
        // takeUntilDestroyed()
      )
      .subscribe({
        // El AuthService ya navega a /login en el tap()
        next: () => {},
        error: () => {
          // Si el backend devolviera error, igual quedas deslogueado (el servicio ya pone false)
          this.errorMsg = 'No se pudo cerrar sesión. Inténtalo nuevamente.';
        }
      });
  }
  onAiClick() {}
}
