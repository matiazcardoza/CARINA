import { Component } from '@angular/core';
import { DigitalSignatureService } from './services/digital-signature.service';
import { SignatureParams } from './interface/signature-params.interface';
@Component({
  selector: 'app-digital-signature',
  imports: [],
  templateUrl: './digital-signature.html',
  styleUrl: './digital-signature.css'
})
export class DigitalSignature {
    environment = {
      production: false,
      API_FIRMA_DIGITAL: 'https://firma.tu-proveedor.com/firmar', // <- URL base del proveedor
      STORAGE_URL: 'https://cdn.tu-app.com/',                     // <- donde está tu PDF
      API_URL: 'https://api.tu-app.com/'                          // <- tu backend
    };


  working = false;
  message = '';

  constructor(private signature: DigitalSignatureService) {}

  sign() {
    // const callback = `${this.environment.API_URL}api/signatures/callback` + `?flow_id=${flowId}&step_id=${stepId}&token=${callbackToken}`;
    this.working = true;
    this.message = '';

    const params: SignatureParams = {
      // location_url_pdf: `http://127.0.0.1:8000/api/payments/kardex_02874_249069_20250825_094654.pdf`,
      location_url_pdf: `http://127.0.0.1:8000/api/payments/PDF_NUMERO_1.pdf`,
      post_location_upload: `http://127.0.0.1:8000/api/signatures/callback?valor=12312312312`,
      rol: 'Jefe de RRHH',
      tipo: 'recursos',
      visible_position: false,
      bacht_operation: false,
      npaginas: 1,
      posx: 500,
      posy: 500,
      token: ''
      // post_location_upload: `${this.environment.API_URL}api/firmas-guardar-archivo-firmado-digitalmente?archivo_id=123`,
      // post_location_upload: `http://127.0.0.1:8000/api/payments/kardex_02874_249069_20250825_094654.pdf`,
    };

    this.signature.openSignatureWindow$(params, 'https://sistemas.regionpuno.gob.pe/firma-api/').subscribe({
      // this.signature.openSignatureWindow$(params, 'https://sistemas.regionpuno.gob.pe/firma-api/');
      next: () => this.message = '✅ Firma completada y recibida por el backend.',
      error: (err) => this.message = `❌ ${err?.message || 'Error de firma'}`,
      complete: () => this.working = false
    });
  }
}
