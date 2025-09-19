import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { map, Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { DocumentSignatureUserElement } from '../../features/private/digital-signature-tray/digital-signature-tray';

interface DocumentSignatureApiResponse {
  message: string;
  data: DocumentSignatureUserElement[];
}


@Injectable({
  providedIn: 'root'
})
export class DocumentSignatureService {

  private http = inject(HttpClient);
  private apiUrl = environment.BACKEND_URL;

  getPendingDocuments(): Observable<DocumentSignatureUserElement[]> {
    return this.http.get<DocumentSignatureApiResponse>(`${this.apiUrl}/api/documents-signature/pending`, {
      withCredentials: true
    }).pipe(
      map(response => response.data)
    );
  }
}
