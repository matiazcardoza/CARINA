import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../../../environments/environment';
// import { MovementKardex } from '../interface/movement-kardex.interface';
import { MovementKardex } from '../interface/movement-kardex.interface';
// iportt product
@Injectable({
  providedIn: 'root'
})
export class MyPersonalGetService {
  // private apiUrl = 'http://127.0.0.1:8000/api/orders-silucia';
  private ordenesSilucia = '/api/orders-silucia';
  private productsList = '/api/products';
  // /api/orders-silucia/{orderSilucia}/products
  private options = {withCredentials: true};
  private apiUrl = environment.BACKEND_URL;
  
  constructor(private http:HttpClient){}
  
  getData(): Observable<any>{
    
    // return this.http.get<any>(this.apiUrl,this.options)
    return this.http.get<any>(`${this.apiUrl}${this.ordenesSilucia}`,this.options)
  }
  // Leer todos los usuarios
  getProducts(id:number): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/api/orders-silucia/${id}/products`,this.options)
    // return this.http.get<any>(this.apiUrl,this.options);
  }
  createProducts(id:any, body:any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/api/orders-silucia/${id}/products`,body,this.options)
    // return this.http.get<any>(this.apiUrl,this.options);
  }
  createKardexMovement(id:any, body:any): Observable<any> {
    // products/{product}/kardex
    return this.http.post<any>(`${this.apiUrl}/api/products/${id}/kardex`,body,this.options)
    // return this.http.get<any>(this.apiUrl,this.options);
  }
  // /products/{product}/movements-kardex
  getKardexByProduct(productId: number): Observable<MovementKardex[]> {
    return this.http.get<MovementKardex[]>(`${this.apiUrl}/api/products/${productId}/movements-kardex`, this.options);
  }


  // Obtener un usuario por ID (para la edición)
  // obtenerUsuario(id: number): Observable<Usuario> {
  //   return this.http.get<Usuario>(`${this.apiUrl}/${id}`);
  // }

  // // Crear nuevo usuario
  // crearUsuario(usuario: Omit<Usuario, 'id'>): Observable<Usuario> {
  //   return this.http.post<Usuario>(this.apiUrl, usuario);
  // }

  // // Actualizar usuario existente
  // actualizarUsuario(id: number, usuario: Usuario): Observable<Usuario> {
  //   return this.http.put<Usuario>(`${this.apiUrl}/${id}`, usuario);
  // }

  // // Eliminar usuario
  // eliminarUsuario(id: number): Observable<void> {
  //   return this.http.delete<void>(`${this.apiUrl}/${id}`);
  // }
}


// import { Injectable } from '@angular/core';
// import { HttpClient } from '@angular/common/http';
// import { Observable } from 'rxjs';

// @Injectable({
//   providedIn: 'root'
// })
// export class MovementRegisterService {
//   private apiUrl = 'http://127.0.0.1:8000/examsdsdsdsdfssdsddfple';
//   constructor(private http: HttpClient) {}
//   sendData(data: any): Observable<any> {
//     return this.http.post<any>(this.apiUrl, data);
//   }
//   seeUrl(){
//     return this.apiUrl;
//   }
// }


// export class ExmapleServicex {
//   // private apiUrl = environment.BACKEND_URL;
//   private apiUrl = 'http://localhost:8000/api/example';
//   constructor(private http: HttpClient) {}

//   sendData(data: any): Observable<any> {
//     // const csrfToken = 'ddddddddddddd';
//     // const headers = new HttpHeaders({
//     //   'XSRF-TOKEN': csrfToken
//     // });

//     const options = {
//       // headers: headers,
//       withCredentials: true
      
//     };
//     return this.http.post<any>(this.apiUrl, data, options)

    
//     // return this.http.post<any>(this.apiUrl,data)
//     // .pipe(
//     //       map(response => response.data)
//     // );
//   }
// }