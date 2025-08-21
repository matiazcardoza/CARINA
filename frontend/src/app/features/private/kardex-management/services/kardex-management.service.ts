import { Injectable } from '@angular/core';
import { HttpClient, HttpResponse } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../../../environments/environment';
import { HttpParams } from '@angular/common/http';
@Injectable({
  providedIn: 'root'
})
export class KardexManagementService {

    private apiUrl = environment.BACKEND_URL;
    private options = {withCredentials: true};


    constructor(private http:HttpClient){}

    // createKardexMovement(orderId:any): Observable<any> {
    //   const apiSilucia_ProductByOrder = `https://sistemas.regionpuno.gob.pe/siluciav2-api/api/ordencompradetallado?numero=${orderId}`;
    //   return this.http.get<any>(apiSilucia_ProductByOrder)
    // }
    getSiluciaProducts(filters: { numero?: string; anio?: number; estado?: string }): Observable<any> {
      const endpoint = `https://sistemas.regionpuno.gob.pe/siluciav2-api/api/ordencompradetallado`;

      let params = new HttpParams();
      if (filters.numero) params = params.set('numero', filters.numero);
      if (filters.anio) params = params.set('anio', filters.anio.toString());
      if (filters.estado) params = params.set('estado', filters.estado);
      console.log("new url: ", params);
      // return this.http.get<any>(endpoint, { params, ...this.options });
      return this.http.get<any>(endpoint, {params});
    }

    createKardexMovement(body:any): Observable<any> {
      // return this.http.post<any>(`${this.apiUrl}/api/products/${id}/kardex`,body,this.options)
      return this.http.post<any>(`${this.apiUrl}/api/movements-kardex`,body,this.options)
    }

    // getKardexMovementBySiluciaBackend(orderSiluciaId: number, productSiluciaId: number): Observable<any> {
    //   // http://localhost:8000/api/silucia-orders/2874/products/249069/movements-kardex
    //   // return this.http.get<any>(`${this.apiUrl}/api/products/${productId}/movements-kardex`, this.options);
    //   return this.http.get<any>(`${this.apiUrl}/api/silucia-orders/${orderSiluciaId}/products/${productSiluciaId}/movements-kardex`, this.options);
    // }
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
      { params }
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
    
}
