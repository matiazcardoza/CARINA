import { Injectable } from '@angular/core';
import { environment } from '../../../../../environments/environment';
import { HttpClient } from '@angular/common/http';

@Injectable({
  providedIn: 'root'
})
export class TestResquestsService {
    private apiUrl = environment.BACKEND_URL;
    private options = {withCredentials: true};
    constructor(private http:HttpClient){}
    
    getData(data: any) {
      // return this.http.get<any>(`${this.apiUrl}/api/get_user_roles`,this.options);
      return this.http.get<any>(`${this.apiUrl}/api/products`,this.options);
    }
}
