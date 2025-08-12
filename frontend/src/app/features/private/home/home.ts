import { Component, signal } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../../services/auth';

@Component({
  selector: 'app-home',
  imports: [],
  templateUrl: './home.html',
  styleUrl: './home.css'
})
export class Home {
  constructor(
    private authService: AuthService,
    private router: Router
  ) {}
  
  count = 0;
  secondCount = signal(0);

  increment(){
    this.count ++ ;
  }
  decrement(){
    this.count -- ;
  }
  reset() {
    this.count = 0; // vuelve a cero
  }



  ngOnInit() {
    console.log("mi valor cargado en el primer render")
    setTimeout(() => {
      this.count = 8 // Aquí Angular no se entera, no repinta en zoneless
      this.secondCount.update(c => c + 1 );
    }, 1000);
  }

  logout() {
    this.authService.logout().subscribe({
      next: () => {
        // Elimina el token o la sesión local
        localStorage.removeItem('user');
        // Redirige al usuario a la página de login
        this.router.navigate(['/login']);
      },
      error: (err) => {
        console.error('Logout failed', err);
        // Aunque haya un error (ej. 401 Unauthorized), la sesión ya no es válida en el backend,
        // por lo que igualmente redirigimos.
        this.router.navigate(['/login']);
      }
    });
  }

  ngAfterViewInit(){ /* ya hay DOM/hijos */ }
  ngOnDestroy(){ /* limpiar timers/subscripciones */ }

}
