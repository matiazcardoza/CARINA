import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface FirmaDigitalParams {
  location_url_pdf: string;
  location_logo?: string;
  post_location_upload: string;
  asunto: string;
  rol: string;
  tipo: string;
  status_position: string;
  visible_position: number;
  bacht_operation: number;
  npaginas?: number;
  token: string;
  generar?: boolean;
  ruta_param?: string;
}

@Injectable({
  providedIn: 'root'
})
export class SignatureService {
  private apiUrl = 'https://sistemas.regionpuno.gob.pe/firma-api';

  firmaDigital(params: FirmaDigitalParams): Observable<any> {
    return new Observable(observer => {
      const urlParams = new URLSearchParams();
      
      Object.keys(params).forEach(key => {
        const value = (params as any)[key];
        if (value !== undefined && value !== null) {
          urlParams.append(key, value.toString());
        }
      });

      const fullUrl = `${this.apiUrl}?${urlParams.toString()}`;

      console.log('URL generada para firma digital:', fullUrl);
      const popup = window.open(
        fullUrl,
        'FirmaDigital',
        'width=650,height=300,menubar=no,toolbar=no,location=no,status=no,scrollbars=no,resizable=no'
      );

      if (!popup) {
        observer.error('No se pudo abrir la ventana de firma. Verifique que no esté bloqueada por el navegador.');
        return;
      }

      const messageHandler = (event: MessageEvent) => {

        if (event.data) {
          if (event.data.status === 'success') {
            window.removeEventListener('message', messageHandler);
            popup.close();
            observer.next(event.data.message || 'Firma completada exitosamente');
            observer.complete();
          } else if (event.data.status === 'cancel' || event.data.status === 'error') {
            window.removeEventListener('message', messageHandler);
            popup.close();
            observer.error(event.data.message || 'Firma cancelada o error en el proceso');
          }
        }
      };

      window.addEventListener('message', messageHandler, false);

      const checkClosed = setInterval(() => {
        if (popup.closed) {
          clearInterval(checkClosed);
          window.removeEventListener('message', messageHandler);
          observer.error('Ventana de firma cerrada por el usuario');
        }
      }, 1000);

      return () => {
        window.removeEventListener('message', messageHandler);
        if (!popup.closed) {
          popup.close();
        }
        clearInterval(checkClosed);
      };
    });
  }

  async getTotalPages(pdfUrl: string): Promise<number> {
    try {
      return 1;
    } catch (error) {
      console.error('Error obteniendo páginas del PDF:', error);
      return 1;
    }
  }
}