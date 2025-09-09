import { Injectable } from '@angular/core';
import { HttpClient, HttpResponse } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../../../environments/environment';
import { HttpParams } from '@angular/common/http';
import { filter, LaravelPagination } from '../interfaces/kardex-management.interface';
import { Pecosa } from '../interfaces/kardex-management.interface';
@Injectable({
  providedIn: 'root'
})
export class KardexManagementService {

    private apiUrl = environment.BACKEND_URL;
    private options = {withCredentials: true};

    constructor(private http:HttpClient){}
    // https://sistemas.regionpuno.gob.pe/siluciav2-api/api/ordencompradetallado  //api original de silucia
    getSiluciaProducts(filters: filter): Observable<any> {
      const endpoint = `${this.apiUrl}/api/silucia-orders`;
      let params = new HttpParams();
      if (filters.numero)   params = params.set('numero', filters.numero);
      if (filters.anio)     params = params.set('anio', filters.anio.toString());
      if (filters.desmeta)  params = params.set('desmeta', filters.desmeta);
      if (filters.siaf)     params = params.set('siaf', filters.siaf);
      if (filters.ruc)      params = params.set('ruc', filters.ruc);
      if (filters.rsocial)  params = params.set('rsocial', filters.rsocial);
      if (filters.email)    params = params.set('email', filters.email);
      if (filters.page)     params = params.set('page', String(filters.page));
      if (filters.per_page) params = params.set('per_page', String(filters.per_page));

      return this.http.get<any>(endpoint, {...this.options, params});
    }

    createKardexMovement(body:any): Observable<any> {
      // return this.http.post<any>(`${this.apiUrl}/api/movements-kardex`,body,this.options)
      return this.http.post<any>(`${this.apiUrl}/api/movements-kardex-for-pecosas`,body,this.options)
    }

    getKardexMovementBySiluciaBackend(
      numero: number | string,
      idcompradet: number,
      opts?: { page?: number; per_page?: number }
    ) {
      const params = new HttpParams({
        fromObject: {
          page: String(opts?.page ?? 1),
          per_page: String(opts?.per_page ?? 50),
        },
      });

      return this.http.get<any>(
        `${this.apiUrl}/api/silucia-orders/${encodeURIComponent(String(numero))}/products/${idcompradet}/movements-kardex`,
        {
          ...this.options, 
          params 
        }
      );
    }

    downloadPdf(id_order_silucia:number,id_product_silucia:number ): Observable<HttpResponse<Blob>> {
      // return this.http.get(`${this.apiUrl}/api/pdf`, {
      // return this.http.get(`${this.apiUrl}/api/products/${id}/movements-kardex/pdf`, {
      return this.http.get(`${this.apiUrl}/api/silucia-orders/${id_order_silucia}/products/${id_product_silucia}/movements-kardex/pdf`, {
        responseType: 'blob',
        observe: 'response',
        withCredentials: true,                 // si usas cookies/Sanctum
        headers: { Accept: 'application/pdf' } // opcional
      });
    }
    
    getPersonByDni(dni: string): Observable<any> {
      return this.http.get<any>(`${this.apiUrl}/api/people/${dni}`,this.options);
    }
    
    getProductsFromOwnDatabase(filters: { 
      numero?: string; anio?: number; item?: string; desmeta?: string; siaf?: string;
      ruc?: string; rsocial?: string; email?: string;
      page?: number; per_page?: number; 
      sort_field?: string; sort_order?: 'asc'|'desc';
    }): Observable<any> {
      const endpoint = `${this.apiUrl}/api/products`; // sin slash final

      let params = new HttpParams();
      if (filters.numero)   params = params.set('numero', filters.numero);
      if (filters.anio)     params = params.set('anio', String(filters.anio));
      if (filters.desmeta)  params = params.set('desmeta', filters.desmeta);
      if (filters.siaf)     params = params.set('siaf', filters.siaf);
      if (filters.ruc)      params = params.set('ruc', filters.ruc);
      if (filters.rsocial)  params = params.set('rsocial', filters.rsocial);
      if (filters.email)    params = params.set('email', filters.email);
      if (filters.item)     params = params.set('item', filters.item);

      if (filters.page)      params = params.set('page', String(filters.page));
      if (filters.per_page)  params = params.set('per_page', String(filters.per_page));
      if (filters.sort_field) params = params.set('sort_field', filters.sort_field);
      if (filters.sort_order) params = params.set('sort_order', filters.sort_order);

      return this.http.get<any>(endpoint, { params, ...this.options });
    }

    getPdfReporter(data: any) {
      // return this.http.get<any>(`${this.apiUrl}/api/get_user_roles`,this.options);
      return this.http.get<any>(`${this.apiUrl}/api/products`,this.options);
    }

    getSiluciaPecosas(filters: filter): Observable<LaravelPagination<Pecosa>>{
      const endpoint = `${this.apiUrl}/api/silucia-pecosas`;
      let params = new HttpParams();
      if (filters.numero)   params = params.set('numero', filters.numero);
      if (filters.anio)     params = params.set('anio', filters.anio.toString());
      if (filters.desmeta)  params = params.set('desmeta', filters.desmeta);
      if (filters.siaf)     params = params.set('siaf', filters.siaf);
      if (filters.ruc)      params = params.set('ruc', filters.ruc);
      if (filters.rsocial)  params = params.set('rsocial', filters.rsocial);
      if (filters.email)    params = params.set('email', filters.email);
      if (filters.page)     params = params.set('page', String(filters.page));
      if (filters.per_page) params = params.set('per_page', String(filters.per_page));

      return this.http.get<LaravelPagination<Pecosa>>(endpoint, {...this.options, params});
    }

}
