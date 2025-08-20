import { ApplicationConfig, provideBrowserGlobalErrorListeners, provideZonelessChangeDetection } from '@angular/core';
import { provideRouter } from '@angular/router';
import { routes } from './app.routes';
import { provideClientHydration, withIncrementalHydration } from '@angular/platform-browser';
import { provideHttpClient, withInterceptors, withXsrfConfiguration } from '@angular/common/http';
import { csrfInterceptor } from './services/AuthService/csrf-interceptor';
import { provideAnimations } from '@angular/platform-browser/animations';
import { MatNativeDateModule, MAT_DATE_LOCALE } from '@angular/material/core';
import { importProvidersFrom } from '@angular/core';

/* Proveedores para primeng */
import { provideAnimationsAsync } from '@angular/platform-browser/animations/async';
import { providePrimeNG } from 'primeng/config';
import Aura from '@primeuix/themes/aura';
import Material from '@primeuix/themes/material';
import Lara from '@primeuix/themes/lara';
import Nora from '@primeuix/themes/nora';
// Aura – look moderno por defecto.

// Material – inspirado en Material Design v2.

// Lara – basado en Bootstrap.

// Nora – estilo “enterprise”
export const appConfig: ApplicationConfig = {
  providers: [
    // provideAnimations(),

    providePrimeNG({
      theme: { 
        preset:Aura,
        options: {
          darkModeSelector: 'none' // o false
        } 
      },
      ripple: true, // opcional
    }),
    provideBrowserGlobalErrorListeners(),
    provideZonelessChangeDetection(),
    provideRouter(routes),
    provideAnimationsAsync(),
    provideHttpClient(
      withInterceptors([csrfInterceptor]),
      withXsrfConfiguration({
        cookieName: 'XSRF-TOKEN',
        headerName: 'X-XSRF-TOKEN'
      })
    ),
    importProvidersFrom(MatNativeDateModule),
    { provide: MAT_DATE_LOCALE, useValue: 'es-ES' }
  ]
};