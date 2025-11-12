import { Component, inject, input, output, signal, ChangeDetectorRef } from '@angular/core';
import { Toolbar } from 'primeng/toolbar';
import { AvatarModule } from 'primeng/avatar';
import { ButtonModule } from 'primeng/button';
import { InputTextModule } from 'primeng/inputtext';
import { MenuItem } from 'primeng/api';
import { MenuModule } from 'primeng/menu';
import { AuthService } from '../../services/AuthService/auth';
import { finalize } from 'rxjs';
import { OverlayBadgeModule } from 'primeng/overlaybadge';
import { TooltipModule } from 'primeng/tooltip';
import { UsersService } from '../../services/UsersService/users-service';
import { MatDialog } from '@angular/material/dialog';
import { ProfileForm } from './profile/profile-form/profile-form';

const DARK_CLASS = 'my-app-dark';
const STORAGE_KEY = 'isDark';

@Component({
  selector: 'app-navbar',
  imports: [
    Toolbar, 
    AvatarModule, 
    ButtonModule,
    InputTextModule,
    MenuModule,
    OverlayBadgeModule,
    TooltipModule
  ],
  templateUrl: './navbar.html',
  styleUrl: './navbar.css',
})
export class Navbar {
  private auth = inject(AuthService);
  private usersService = inject(UsersService);
  private dialog = inject(MatDialog);
  private cdr = inject(ChangeDetectorRef);
  
  sentOpenValue = output<boolean>();
  isOpen = input<boolean>(false);
  loading = false;
  isAuthenticated$ = this.auth.isAuthenticated$;
  errorMsg = '';
  iconName = signal<string>('pi pi-moon');
  
  profileItems: MenuItem[] = [
    { label: 'Mi perfil',           icon: 'pi pi-user',     command: () => this.onGoProfile()    },
    { label: 'Cambiar contraseña',  icon: 'pi pi-cog',      command: () => this.onGoSettings()   },
    { separator: true },
    { label: 'Cerrar sesión',       icon: 'pi pi-sign-out', command: () => this.onLogout()       }
  ];

  handleOpenSidebar(value: boolean){
    this.sentOpenValue.emit(true);
  }

  ngOnInit(){ 
    this.initFromStorage(); 
    this.setPrimaryTheme();
  }

  initFromStorage() {
    const isDark = localStorage.getItem(STORAGE_KEY) === '1';
    document.documentElement.classList.toggle(DARK_CLASS, isDark);
    this.setMaterialTheme(isDark);
  }

  onGoProfile(){
    // Implementar si es necesario
  }

  onGoSettings(){
    // Abrir el diálogo de cambiar contraseña con Material Dialog
    const dialogRef = this.dialog.open(ProfileForm, {
      width: '95vw',
      maxWidth: '700px',
      height: '40vh',
      disableClose: false, // Permite cerrar con ESC o click fuera
      autoFocus: true,
      panelClass: 'password-change-dialog' // Clase CSS personalizada si necesitas
    });

    dialogRef.afterClosed().subscribe((result) => {
      if (result) {
        // Si el formulario retorna un resultado exitoso
        Promise.resolve().then(() => {
          this.cdr.detectChanges();
          console.log('Contraseña actualizada:', result);
          // Aquí puedes recargar datos o mostrar un mensaje de éxito
        });
      }
    });
  }

  onLogout(){
    console.log("log out");
    if (this.loading) return;
    this.loading = true;
    this.errorMsg = '';

    this.auth.logout()
      .pipe(finalize(() => (this.loading = false)))
      .subscribe({
        next: () => {},
        error: () => {
          this.errorMsg = 'No se pudo cerrar sesión. Inténtalo nuevamente.';
        }
      });
  }

  onAiClick(){
    // Implementar si es necesario
  }

  toggleTheme(){
    const el = document.documentElement;
    const isDark = !el.classList.contains(DARK_CLASS);
    el.classList.toggle(DARK_CLASS, isDark);
    localStorage.setItem(STORAGE_KEY, isDark ? '1' : '0');
    this.setMaterialTheme(isDark);
    isDark ? this.iconName.set('pi pi-sun') : this.iconName.set('pi pi-moon');
  }

  setPrimaryTheme(){
    // Implementación de tema
  }

  setMaterialTheme(isDark: boolean) {
    const linkEl = document.getElementById('mat-theme') as HTMLLinkElement | null;
    if (!linkEl) return;
    linkEl.href = isDark ? 'material-dark.css' : 'material-light.css';
  }

  onReportIncident(){
    this.usersService.getUserIncidencia().subscribe({
      next: (response) => {
        const user = response[0];
        const dni = encodeURIComponent(user.num_doc);
        const nombre = encodeURIComponent(`${user.persona_name} ${user.last_name}`);
        const sistemaId = 20;
        const url = `https://sistemas.regionpuno.gob.pe/incidencias/ticketcreate/?dni=${dni}&nombre=${nombre}&sistema_id=${sistemaId}`;
        window.open(url, '_blank');
      },
      error: (error) => {
        console.error('Error al obtener datos del usuario:', error);
      }
    });
  }
}