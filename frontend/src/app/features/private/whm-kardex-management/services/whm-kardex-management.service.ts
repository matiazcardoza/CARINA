// import { Injectable } from '@angular/core';
// import { HttpClient } from '@angular/common/http';
// import { environment } from '../../../../../environments/environment';
// @Injectable({
//   providedIn: 'root'
// })
// export class WhmKardexManagementService {

//   private apiUrl = environment.BACKEND_URL;
//   private options = {withCredentials: true};
//   constructor(private http:HttpClient){
//   }

//   get(){
//     return this.http.get(`${this.apiUrl}/api/me/obras`,this.options);
//   }

// }
// ----------------------------------------------------------------------------------

// whm-kardex-management.service.ts
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../../../environments/environment';
export type Obra = { id: number; nombre: string; codigo: string };
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
  // ...otros campos del paginador
};

@Injectable({ providedIn: 'root' })
export class WhmKardexManagementService {
  // private base = '/api';
  private apiUrl = environment.BACKEND_URL;
  private options = {withCredentials: true};
  constructor(private http: HttpClient) {}

  getObras(): Observable<Obra[]> {
    return this.http.get<Obra[]>(`${this.apiUrl}/api/me/obras`,this.options);
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

//   getOrdenesComprax(obraId: number, search?: string) {
//     const headers = new HttpHeaders({ 'X-Obra-Id': String(obraId) });
//     let params = new HttpParams();
//     if (search) params = params.set('q', search);

//     return this.http
//       .get<Page<OC>>(`${this.apiUrl}/api/ordenes-compra`, { ...this.options, headers, params })
//       .pipe(
//         map(page => (page?.data ?? []).map(oc => ({
//           ...oc,
//           monto_total: oc.monto_total as unknown as number
//             ? +((oc as any).monto_total)
//             : +(oc.monto_total as unknown as string) || 0
//         })))
//       );
// }

  getPecosasDeOrden(obraId: number, ordenId: number): Observable<Pecosa[]> {
    const headers = new HttpHeaders({ 'X-Obra-Id': String(obraId) }); // ← sólo aquí
    return this.http.get<Pecosa[]>(`${this.apiUrl}/api/ordenes-compra/${ordenId}/pecosas`, {...this.options, headers });
  }
}

