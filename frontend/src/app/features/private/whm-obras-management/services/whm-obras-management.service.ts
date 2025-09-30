
import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { environment } from '../../../../../environments/environment';
import { MetasDetalladoResponse } from '../interfaces/whm-obras-management.interface';
import { Filters } from '../interfaces/whm-obras-management.interface';

export type Obra = {
  id: number;
  idmeta_silucia: string;
  anio: string;
  codmeta: string;
  nombre: string | null;
  desmeta: string | null;
  nombre_corto: string | null;
};
export type ObratoImport = {
  idmeta: string;
  anio: string;
  codmeta: string;
  desmeta: string | null;
};

export type Paginated<T> = {
  data: T[];
  total: number;
  per_page: number;
  current_page: number;
};
@Injectable({
  providedIn: 'root'
})
export class WhmObrasManagementService {
  private apiUrl = environment.BACKEND_URL;
  private options = {withCredentials: true};
  private http = inject(HttpClient);
  private base = '/api/admin';

  getObrasx() {
    // Route::get('/get-all-obras', [AdminCatalogController::class, 'allObras'])->middleware(['role:almacen.superadmin']);
    return this.http.get<Obra[]>(`${this.apiUrl}/api/admin/get-all-obras`,this.options);

  }
  getObrasxx(page = 1, perPage = 10, search = '') {
    let params = new HttpParams()
      .set('page', page)
      .set('per_page', perPage);
    if (search) params = params.set('search', search);
    // return this.http.get<Paginated<Obra>>(`${this.base}/get-all-obras`, { params });
    return this.http.get<Obra[]>(`${this.apiUrl}/api/admin/get-all-obras`,this.options);
  }
  getObras(page = 1, perPage = 10, search = '') {
    console.log("se realiza la consulta",  page, perPage, search);
    let params = new HttpParams()
      .set('page', String(page))
      .set('per_page', String(perPage));
    if (search) params = params.set('search', search);

    // tu endpoint real (según el JSON que pegaste)
    return this.http.get<Obra[]>(`${this.apiUrl}/api/admin/get-all-obras`,{...this.options, params});
    // return this.http.get<Paginated<Obra>>(`${this.base}/get-all-obras`, { params });
  }

  getObrasSilucia(page = 1, perPage = 10, filters: Filters){
    let params = new HttpParams()
      .set('page', page)
      .set('per_page', perPage);
      if(filters.anio){
        params = params.set('anio', filters.anio)
      }

      if(filters.codmeta){
        params = params.set('codmeta', filters.codmeta)
      }


    return this.http.get<MetasDetalladoResponse>(`${this.apiUrl}/api/admin/obras`,{...this.options, params});
  }

  importUsers(obraId: number) {
    return this.http.post(`${this.apiUrl}/api/admin/obras/${obraId}/import-users`, {},this.options);
  }

  importAndAttach(meta: ObratoImport) {
    console.log(meta);
    return this.http.post(`${this.apiUrl}/api/admin/obras/import`, { meta }, { withCredentials: true });
  }
}
