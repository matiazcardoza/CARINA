// src/app/services/auth.service.ts
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';

import { Observable, catchError, of, throwError, tap, switchMap } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({
    providedIn: 'root',
})
export class AuthService {
    private baseUrl = 'http://localhost:8000';
    private apiUrl = environment.backendUrl;
    constructor(private http: HttpClient) {}

    getCsrfCookie(): Observable<any> {
        console.log('🔄 Obteniendo CSRF cookie desde:', `${this.baseUrl}/sanctum/csrf-cookie`);
        
        return this.http.get(`${this.baseUrl}/sanctum/csrf-cookie`, { 
            withCredentials: true,
            observe: 'response' // Para ver headers de respuesta
        }).pipe(
            tap(response => {
                console.log('📋 Headers de respuesta CSRF:', response.headers.keys());
                console.log('🍪 Set-Cookie headers:', response.headers.get('set-cookie'));
            })
        );
    }

    private getCsrfTokenFromCookie(): string | null {
        // Buscar la cookie en el dominio correcto (localhost:8000)
        const cookies = document.cookie.split(';');
        
        for (let cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'XSRF-TOKEN') {
                const decodedValue = decodeURIComponent(value);
                console.log('🔑 Token CSRF encontrado:', decodedValue.substring(0, 20) + '...');
                return decodedValue;
            }
        }
        
        console.warn('⚠️ No se encontró token CSRF en cookies');
        console.log('🍪 Cookies disponibles:', document.cookie);
        return null;
    }

    login(credentials: any): Observable<any> {
        console.log('🔍 Iniciando login para:', credentials.email);
        
        return this.getCsrfCookie().pipe(
            switchMap(() => {
                console.log('📤 Enviando credenciales de login');
                
                // Esperar un poco para que la cookie se establezca
                return new Promise(resolve => setTimeout(resolve, 100)).then(() => {
                    const csrfToken = this.getCsrfTokenFromCookie();
                    
                    if (!csrfToken) {
                        throw new Error('No se pudo obtener el token CSRF');
                    }
                    
                    const headers = new HttpHeaders({
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-XSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    });
                    
                    console.log('📋 Headers de petición:', {
                        'X-XSRF-TOKEN': csrfToken.substring(0, 20) + '...',
                        'Content-Type': 'application/json'
                    });
                    
                    return this.http.post(`${this.baseUrl}/login`, credentials, { 
                        withCredentials: true,
                        headers: headers
                    });
                });
            }),
            tap(response => console.log('✅ Login exitoso:', response)),
            catchError(error => {
                console.error('❌ Error en login:', error);
                console.log('🔍 Debug info:');
                console.log('- URL:', `${this.baseUrl}/login`);
                console.log('- Cookies:', document.cookie);
                console.log('- withCredentials: true');
                return throwError(() => error);
            })
        );
    }

    logout(): Observable<any> {
        return this.http.post(`${this.apiUrl}/logout`, {}, { withCredentials: true }).pipe(
            catchError((err) => {
                console.error('Error durante el logout:', err);
                return of(null);
            })
        );
    }

    register(credentials: any): Observable<any> {
        return this.http.post(`${this.apiUrl}/register`, credentials, { withCredentials: true });
    }
}