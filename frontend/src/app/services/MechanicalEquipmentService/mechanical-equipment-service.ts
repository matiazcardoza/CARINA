import { Injectable, inject } from '@angular/core';
import { environment } from '../../../environments/environment';
import { map, Observable } from 'rxjs';
import { HttpClient } from '@angular/common/http';
import { MechanicalEquipmentElement } from '../../features/private/mechanical-equipment/mechanical-equipment';

interface MechanicalEquipmentApiResponse {
  message: string;
  data: MechanicalEquipmentElement[];
}

interface SingleApiResponse {
  message: string;
  data: MechanicalEquipmentElement;
}

export interface CreateMechanicalEquipmentData {
  equipment: string;
  ability: string;
  brand: string;
  model: string;
  serial_number: string;
  year: number;
  plate?: string;
  status: string;
}

@Injectable({
  providedIn: 'root'
})



export class MechanicalEquipmentService {
  private http = inject(HttpClient);
  private apiUrl = environment.BACKEND_URL;

  getMechanicalEquipment(): Observable<MechanicalEquipmentElement[]> {
    return this.http.get<MechanicalEquipmentApiResponse>(`${this.apiUrl}/api/mechanical-equipment`, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  createMechanicalEquipment(mechanicalEquipmentData: CreateMechanicalEquipmentData): Observable<MechanicalEquipmentElement> {
    return this.http.post<SingleApiResponse>(`${this.apiUrl}/api/mechanical-equipment`, mechanicalEquipmentData, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  updateMechanicalEquipment(mechanicalEquipmentData: CreateMechanicalEquipmentData): Observable<MechanicalEquipmentElement> {
    return this.http.put<SingleApiResponse>(`${this.apiUrl}/api/mechanical-equipment`, mechanicalEquipmentData, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }

  deleteMechanicalEquipment(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/api/mechanical-equipment/${id}`, {
      withCredentials: true
    });
  }

  getMetaByCode(codmeta: string): Observable<any> {
    const apiUrl = `https://sistemas.regionpuno.gob.pe/siluciav2-api/api/metasdetallado?codmeta=${codmeta}`;
    return this.http.get<any>(apiUrl);
  }
}
