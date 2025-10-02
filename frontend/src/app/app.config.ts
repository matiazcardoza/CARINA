import { ApplicationConfig, provideBrowserGlobalErrorListeners, provideZonelessChangeDetection } from '@angular/core';
import { provideRouter } from '@angular/router';
import { routes } from './app.routes';
import { provideClientHydration, withIncrementalHydration } from '@angular/platform-browser';
import { provideHttpClient, withInterceptors, withXsrfConfiguration } from '@angular/common/http';
import { csrfInterceptor } from './services/AuthService/csrf-interceptor';
import { provideAnimations } from '@angular/platform-browser/animations';
import { MatNativeDateModule, MAT_DATE_LOCALE } from '@angular/material/core';
import { importProvidersFrom } from '@angular/core';
import { provideAnimationsAsync } from '@angular/platform-browser/animations/async';
import MyPreset from '../theme/mypreset';
import { providePrimeNG } from 'primeng/config';
import { authExpirationInterceptor } from './services/AuthService/auth-expiration.interceptor';
export const appConfig: ApplicationConfig = {
  providers: [
    provideBrowserGlobalErrorListeners(),
    provideZonelessChangeDetection(),
    provideRouter(routes),
    provideAnimationsAsync(),
    // provideAnimations(),
    providePrimeNG({
      theme: { 
        // preset:Aura,
        preset:MyPreset,
        options: {
          darkModeSelector: '.my-app-dark' ,
        } 
      },
      // ripple: true, 
    }),
    provideHttpClient(
      withInterceptors([csrfInterceptor, authExpirationInterceptor]),
      withXsrfConfiguration({
        cookieName: 'XSRF-TOKEN',
        headerName: 'X-XSRF-TOKEN'
      })
    ),
    importProvidersFrom(MatNativeDateModule),
    { provide: MAT_DATE_LOCALE, useValue: 'es-ES' }
  ]
};