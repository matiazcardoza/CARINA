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
import { updatePrimaryPalette, updateSurfacePalette } from '@primeuix/themes';
const DARK_CLASS = 'my-app-dark';
const STORAGE_KEY = 'isDark';

@Component({
  selector: 'app-navbar',
  imports: [
    Toolbar, 
    AvatarModule, 
    ButtonModule,
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
    { label: 'cambiar tema',   icon: 'pi pi-pencil',       command: () => this.toggleDark() },
    { separator: true },
    { label: 'Cerrar sesión', icon: 'pi pi-sign-out', command: () => this.onLogout() }
  ];
  ngOnInit(){ 
    this.initFromStorage(); 
    this.setPrimaryTheme();
  }
  // handleOpenSidebar(value: boolean) { this.sentOpenValue.emit(value); }
  initFromStorage() {
    const isDark = localStorage.getItem(STORAGE_KEY) === '1';
    document.documentElement.classList.toggle(DARK_CLASS, isDark);
  }

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
  toggleDark() {
    const el = document.documentElement;
    const isDark = !el.classList.contains(DARK_CLASS);
    el.classList.toggle(DARK_CLASS, isDark);
    localStorage.setItem(STORAGE_KEY, isDark ? '1' : '0');
  }

  setPrimaryTheme(){
    // updatePrimaryPalette({
    //   50: '{violet.50}', 100: '{violet.100}', 200: '{violet.200}',
    //   300: '{violet.300}', 400: '{violet.400}', 500: '{violet.500}',
    //   600: '{violet.600}', 700: '{violet.700}', 800: '{violet.800}',
    //   900: '{violet.900}', 950: '{violet.950}',
    // });

    // updateSurfacePalette({
    //   50: '{violet.50}', 100: '{violet.100}', 200: '{violet.200}',
    //   300: '{violet.300}', 400: '{violet.400}', 500: '{violet.500}',
    //   600: '{violet.600}', 700: '{violet.700}', 800: '{violet.800}',
    //   900: '{violet.900}', 950: '{violet.950}',
    // })
  }
}
