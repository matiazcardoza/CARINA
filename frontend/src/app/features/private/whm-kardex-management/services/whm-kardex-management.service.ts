
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpParams, HttpResponse  } from '@angular/common/http';
import { environment } from '../../../../../environments/environment';
import { SignatureParams } from '../interfaces/whm-kardex-management.interface';
import { defer, filter, finalize, fromEvent, map, Observable, take, throwError, timeout } from 'rxjs';
import { FiltersPecosas } from '../interfaces/whm-kardex-management.interface';
export type Obra = { id: number; nombre: string; codigo: string };
export interface ObraLite { id: number; nombre: string; codmeta?: string; codigo?: string; anio?: string }

export interface MovementsPage { movements: { data: any[]; total: number; per_page: number; }; }
export type OC   = { id: number; ext_order_id: string; fecha: string; proveedor: string; monto_total: number };
// import { OperarioDTO } from '../interfaces/whm-kardex-management.interface';
import { ApiResponseOperarios } from '../interfaces/whm-kardex-management.interface';
import { OrdenCompraDetalladoFilters, OrdenCompraDetalladoRow, OrdenCompraDetalladoResponse } from '../interfaces/whm-kardex-management.interface';
import { KardexMovementFilters, KardexMovementDetallado, KardexMovementResponse, KardexMovementRow } from '../interfaces/whm-kardex-management.interface';

export type Page<T> = {
  data: T[];
  current_page: number;
  per_page: number;
  total: number;
};

import { PecosaResponse } from '../interfaces/whm-kardex-management.interface';
import { Pecosa } from '../interfaces/whm-kardex-management.interface';
@Injectable({ providedIn: 'root' })
export class WhmKardexManagementService {
  private apiUrl = environment.BACKEND_URL;
  private options = {withCredentials: true};
  constructor(private http: HttpClient) {}

  getObras(): Observable<Obra[]> {
    return this.http.get<Obra[]>(`${this.apiUrl}/api/me/obras`,this.options);
    
  }
  // import { FiltersPecosas } from '../interfaces/whm-kardex-management.interface';
  
  getItemPecosas(obraId: number | null, page: number, perPage: number, filters: FiltersPecosas) {
      let params = new HttpParams()
        .set('page',page)
        .set('per_page', perPage);

      if(filters.anio){
        params = params.set('anio', filters.anio)
      }

      if(filters.numero){
        params = params.set('numero', filters.numero)
      }

      return this.http.get<PecosaResponse<Pecosa>>(`${this.apiUrl}/api/obras/${obraId}/item-pecosas`, 
      {
        ...this.options,
        params: params, 
        headers: {'X-Obra-Id': String(obraId) }
      });
  }
  getOrdenCompraDetallado(obraId: number | null, page: number, perPage: number, filters: OrdenCompraDetalladoFilters) {
      let params = new HttpParams()
        .set('page',page)
        .set('per_page', perPage);

      if(filters.anio){
        params = params.set('anio', filters.anio)
      }

      if(filters.numero){
        params = params.set('numero', filters.numero)
      }

      return this.http.get<OrdenCompraDetalladoResponse<OrdenCompraDetalladoRow>>(`${this.apiUrl}/api/obras/${obraId}/ordenes-compra-detallado`, 
      {
        ...this.options,
        params: params, 
        headers: {'X-Obra-Id': String(obraId) }
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
  deleteReport(obraId: number | null, reportId: number) {
    const headers = new HttpHeaders({ 'X-Obra-Id': String(obraId) }); // ← sólo aquí
    return this.http.delete(`${this.apiUrl}/api/reports/${reportId}`, { ...this.options, headers });
  }

  getPecosasDeOrden(obraId: number, ordenId: number): Observable<Pecosa[]> {
    const headers = new HttpHeaders({ 'X-Obra-Id': String(obraId) }); // ← sólo aquí
    return this.http.get<Pecosa[]>(`${this.apiUrl}/api/ordenes-compra/${ordenId}/pecosas`, {...this.options, headers });
  }
  getOperarios(obraId: number | null): Observable<ApiResponseOperarios> {
    const headers = new HttpHeaders({ 'X-Obra-Id': String(obraId) }); // ← sólo aquí
    // /obras/{obra}/users/operarios
    return this.http.get<any>(`${this.apiUrl}/api/users-operarios`, {...this.options, headers });
  }

  createKardexMovement(obraId: number |null, itemPecosaId:number |null, payload: any,   ) {
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

  getKardexMovementDetails(obraId: number | null, ordenCopraDetalladoId: number,  page: number, perPage: number, filters: KardexMovementFilters) {
      let params = new HttpParams()
        .set('page',page)
        .set('per_page', perPage);

      if(filters.anio){
        params = params.set('anio', filters.anio)
      }

      if(filters.numero){
        params = params.set('numero', filters.numero)
      }
      // kardex-movements/{ordenCompraDetallado}
          // Route::get('ordenes-compra-detallado/{ordenCompra}/movements-kardex', [OrdenCompraDetalladoController::class, 'getOrdenesDeCompra']);
      return this.http.get<KardexMovementResponse<KardexMovementRow>>(`${this.apiUrl}/api/ordenes-compra-detallado/${ordenCopraDetalladoId}/movements-kardex`, 
      {
        ...this.options,
        params: params, 
        headers: {'X-Obra-Id': String(obraId) }
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
  downloadPdfOrdenCompra(obraId: number | null, idOrdenCompraDetallado: number) {
      let headers = new HttpHeaders({ Accept: 'application/pdf' });
      if (obraId !== null) {
        headers = headers.set('X-Obra-Id', String(obraId));
      }
      return this.http.get(
            // Route::get('ordenes-compra-detallado/{ordenCompraDetallado}/movements-kardex/pdf'
        `${this.apiUrl}/api/ordenes-compra-detallado/${idOrdenCompraDetallado}/movements-kardex/pdf`,
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
  postSavePersonByDni(obraId: number |null , dni: string): Observable<any> {
    console.log("enviado con datos: ", obraId, "y: ", dni)
    const headers = new HttpHeaders({ 'X-Obra-Id': String(obraId) });
    return this.http.get<any>(`${this.apiUrl}/api/people-save/${dni}`,{...this.options, headers});
  }

  userRolesByObra(obraId: number){
    const headers = new HttpHeaders({ 'X-Obra-Id': String(obraId) });
    return this.http.get<any>(`${this.apiUrl}/api/roles-by-obra`,{...this.options, headers});
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

