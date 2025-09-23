
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpParams, HttpResponse  } from '@angular/common/http';
import { environment } from '../../../../../environments/environment';
import { SignatureParams } from '../interfaces/whm-kardex-management.interface';
import { defer, filter, finalize, fromEvent, map, Observable, take, throwError, timeout } from 'rxjs';

export type Obra = { id: number; nombre: string; codigo: string };
export interface ObraLite { id: number; nombre: string; codmeta?: string; codigo?: string; }
export interface PecosaLite {
  id: number;
  obra_id: number;
  anio: string;
  numero: string;
  fecha: string;
  prod_proy?: string;
  cod_meta?: string;
  desmeta?: string;
  desuoper?: string;
  destipodestino?: string;
  item?: string;
  desmedida?: string;
  cantidad?: number;
  precio?: number;
  total?: number;
  saldo?: number;
  numero_origen?: string;
  idsalidadet_silucia: number | string;
  idcompradet_silucia?: number | string;
}
export interface PageResp<T> { data: T[]; total: number; per_page: number; }
export interface MovementsPage { movements: { data: any[]; total: number; per_page: number; }; }
export type OC   = { id: number; ext_order_id: string; fecha: string; proveedor: string; monto_total: number };
export type OCx = OC & {
  pecosas?: Pecosa[];
  childLoading?: boolean;
};
export type Pecosa = { idcompradet: number; /* ...campos que ya tienes... */ };

export type Page<T> = {
  data: T[];
  current_page: number;
  per_page: number;
  total: number;
};

@Injectable({ providedIn: 'root' })
export class WhmKardexManagementService {
  private apiUrl = environment.BACKEND_URL;
  private options = {withCredentials: true};
  constructor(private http: HttpClient) {}

  getObras(): Observable<Obra[]> {
    return this.http.get<Obra[]>(`${this.apiUrl}/api/me/obras`,this.options);
    
  }

  getItemPecosas(obraId: number, params: { page?: number; per_page?: number; numero?: string; anio?: number }) {
    let p = new HttpParams();
    for (const [k, v] of Object.entries(params || {})) if (v !== undefined && v !== null && v !== '') p = p.set(k, String(v));
    return this.http.get<PageResp<PecosaLite>>(`${this.apiUrl}/api/obras/${obraId}/item-pecosas`, {
      params: p, withCredentials: true, headers: { 'X-Obra-Id': String(obraId) }
    });
  }

  getOrdenesCompra(obraId: number, search?: string): Observable<OC[]> {
    const headers = new HttpHeaders({ 'X-Obra-Id': String(obraId) }); // ← sólo aquí
    let params = new HttpParams();
    if (search) params = params.set('q', search);
    return this.http.get<OC[]>(`${this.apiUrl}/api/ordenes-compra`, { ...this.options, headers, params });
  }

  getPecosas(obraId: number, search?: string): Observable<OC[]> {
    const headers = new HttpHeaders({ 'X-Obra-Id': String(obraId) }); // ← sólo aquí
    let params = new HttpParams();
    if (search) params = params.set('q', search);
    return this.http.get<OC[]>(`${this.apiUrl}/api/pecosas`, { ...this.options, headers, params });
  }

  getPecosasDeOrden(obraId: number, ordenId: number): Observable<Pecosa[]> {
    const headers = new HttpHeaders({ 'X-Obra-Id': String(obraId) }); // ← sólo aquí
    return this.http.get<Pecosa[]>(`${this.apiUrl}/api/ordenes-compra/${ordenId}/pecosas`, {...this.options, headers });
  }

  createKardexMovement(payload: any, itemPecosaId:number, obraId: number |null ) {
    const headers = new HttpHeaders({ 'X-Obra-Id': String(obraId) });
    return this.http.post(`${this.apiUrl}/api/kardex-movements/${itemPecosaId}`, payload, { withCredentials: true, headers });
  }

  getKardexMovement(obraId: number | null, itemPecosaId: number, params: { page?: number; per_page?: number }) {
    const headers = new HttpHeaders({ 'X-Obra-Id': String(obraId) });
    let p = new HttpParams();
    for (const [k, v] of Object.entries(params || {})) if (v !== undefined && v !== null) p = p.set(k, String(v));
    return this.http.get<MovementsPage>(`${this.apiUrl}/api/item-pecosas/${itemPecosaId}/movements-kardex`, {
      params: p, withCredentials: true, headers
    });
  }

  downloadPdf(obraId: number | null, idItemPecosa: number) {
      let headers = new HttpHeaders({ Accept: 'application/pdf' });
      if (obraId !== null) {
        headers = headers.set('X-Obra-Id', String(obraId));
      }
      return this.http.get(
        `${this.apiUrl}/api/item-pecosas/${idItemPecosa}/movements-kardex/pdf`,
        {
          observe: 'response',
          reportProgress: true,
          responseType: 'blob',
          withCredentials: true,
          headers
        }
      ) as Observable<HttpResponse<Blob>>;
  }

  getPersonByDni(obraId: number |null , dni: string): Observable<any> {
    const headers = new HttpHeaders({ 'X-Obra-Id': String(obraId) });
    return this.http.get<any>(`${this.apiUrl}/api/people/${dni}`,{...this.options, headers});
  }

  environment = {
    LOGO: 'https://sistemas.regionpuno.gob.pe/sisplan-api/logo_firma_digital.png',
    PROVEEDOR_URL: 'https://sistemas.regionpuno.gob.pe/firma-api/'                         // <- tu backend
  };

  openSignatureWindow$(params: SignatureParams): Observable<void> {
    return defer(() => {
      const timer = 5 * 60 * 1000;
      const trustedOrigin = new URL(this.environment.PROVEEDOR_URL).origin;

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

      const popup = window.open(`${this.environment.PROVEEDOR_URL}?${qs}`, '_blank', 'width=500,height=300');

      if (!popup) return throwError(() => new Error('No se pudo abrir la ventana de firma.'));

      return fromEvent<MessageEvent>(window, 'message').pipe(
        // Acepta solo mensajes de ese popup y ese origen
        filter(ev => ev.origin === trustedOrigin && ev.source === popup),
        take(1),                    // primer mensaje válido
        timeout(timer),         // cortar si no responde a tiempo
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

