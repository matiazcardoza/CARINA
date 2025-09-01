import { Injectable } from '@angular/core';
import { SignatureParams } from '../interface/signature-params.interface';
import { defer, filter, finalize, fromEvent, map, Observable, take, throwError, timeout } from 'rxjs';


@Injectable({
  providedIn: 'root'
})
export class DigitalSignatureService {

    environment = {
      API_FIRMA_DIGITAL: 'https://sistemas.regionpuno.gob.pe/firma-api/', // <- URL base del proveedor
      LOGO: 'https://sistemas.regionpuno.gob.pe/sisplan-api/logo_firma_digital.png'                          // <- tu backend
    };

  openSignatureWindow$(params: SignatureParams,  apiUrl: string = this.environment.API_FIRMA_DIGITAL, timeoutMs = 5 * 60 * 1000): Observable<void> {
    return defer(() => {

      const trustedOrigin = new URL(apiUrl).origin;

      const qs = new URLSearchParams({
        location_url_pdf: params.location_url_pdf,
        location_logo: this.environment.LOGO,
        post_location_upload: params.post_location_upload,
        asunto: params.asunto ?? '',
        rol: params.rol ?? '',
        tipo: params.tipo ?? '',
        status_position: params.status_position ?? '',
        visible_position: String(params.visible_position ?? 'false'),
        bacht_operation: String(params.bacht_operation ?? 'false'),
        npaginas: String(params.npaginas ?? ''),
        posx: String(params.posx ?? ''),
        posy: String(params.posy ?? ''),
        dni: params.dni ?? '',
        tipoSalida: params.tipoSalida ?? '',
        siguienteEnFirmar: params.siguienteEnFirmar ?? '',
        token: params.token ?? ''
      }).toString();

      const popup = window.open(`${apiUrl}?${qs}`, '_blank', 'width=500,height=300');

      if (!popup) return throwError(() => new Error('No se pudo abrir la ventana de firma.'));

      return fromEvent<MessageEvent>(window, 'message').pipe(
        // Acepta solo mensajes de ese popup y ese origen
        filter(ev => ev.origin === trustedOrigin && ev.source === popup),
        take(1),                    // primer mensaje válido
        timeout(timeoutMs),         // cortar si no responde a tiempo
        map(ev => {
          const status = (ev.data as any)?.status;
          if (status === 'success') return;
          if (status === 'cancel') throw new Error('Firma cancelada por el usuario.');
          throw new Error('Respuesta de firma no reconocida.');
        }),
        finalize(() => { try { popup.close(); } catch {} })
      );
      
    });
  }
}
