import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { map, Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { DocumentSignatureUserElement } from '../../features/private/digital-signature-tray/digital-signature-tray';
import { DocumentDailyPartElement } from '../../features/private/digital-signature-tray/form/document-signature/document-signature';

interface DocumentSignatureApiResponse {
  message: string;
  data: DocumentSignatureUserElement[];
}

interface DocumentDailyPartApiResponse {
  message: string;
  data: DocumentDailyPartElement;
  pages: number;
}

export interface UserRoleElement {
  id: number,
  name: string
}

interface UserRoleApiResponse {
  message: string;
  data: UserRoleElement[];
}

interface ResendDocumentData {
  documentId: number | null;
  observation: string;
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

  getWorkLogDocumentSignature(documentId: number): Observable<DocumentDailyPartElement> {
    return this.http.get<DocumentDailyPartApiResponse>(`${this.apiUrl}/api/document-signature/${documentId}`, {
      withCredentials: true,
    }).pipe(
      map(response => ({
        ...response.data,
        pages: response.pages
      }))
    );
  }

  resendDocumentToController(ReturnData: ResendDocumentData): Observable<any> {
    return this.http.post(`${this.apiUrl}/api/document-return/resend-to-controller`, ReturnData, {
      withCredentials: true
    });
  }
}
