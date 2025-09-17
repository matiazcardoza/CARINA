import { Component, Inject } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DailyWorkLogService } from '../../../../../../services/DailyWorkLogService/daily-work-log-service';
import { FirmaDigitalParams, SignatureService } from '../../../../../../services/SignatureService/signature-service';
import { environment } from '../../../../../../../environments/environment';

export interface DocumentDailyPartElement {
  id: number;
  file_path: string;
  state: string;
}

interface DialogData {
  workLogId: number;
  date: string;
}

@Component({
  selector: 'app-daily-work-signature',
  standalone: true,
  imports: [
    CommonModule,
    MatIconModule,
    MatButtonModule,
    MatToolbarModule,
    MatProgressSpinnerModule
  ],
  templateUrl: './daily-work-signature.html',
  styleUrl: './daily-work-signature.css'
})
export class DailyWorkSignature {

  pdfUrl: SafeResourceUrl | null = null;
  pdfUrlString: string = '';
  isLoading = false;
  error = null;
  isSigned = false;
  signatureData: any = null;
  isSigningInProgress = false;
  
  constructor(
    public dialogRef: MatDialogRef<DailyWorkSignature>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData,
    private cdr: ChangeDetectorRef,
    private dailyWorkLogService: DailyWorkLogService,
    private signatureService: SignatureService,
    private sanitizer: DomSanitizer
  ) {}

  ngOnInit(): void {
    this.loadPdfDocument();
  }

  loadPdfDocument(): void {
    this.isLoading = true;
    this.error = null;
    this.pdfUrl = null;
    this.cdr.detectChanges();

    const workLogId = this.data.workLogId;

    this.dailyWorkLogService.getWorkLogDocument(workLogId)
      .subscribe({
        next: (data: DocumentDailyPartElement) => {
          console.log('PDF document response:', data);
          try {
            const pdfPath = data.file_path;
            const fullPdfUrl = `${environment.BACKEND_URL_STORAGE}${pdfPath}`;
            this.pdfUrlString = fullPdfUrl; // Guardar la URL como string para la firma
            this.pdfUrl = this.sanitizer.bypassSecurityTrustResourceUrl(fullPdfUrl);
            this.isLoading = false;
            this.error = null;
          } catch (err) {
            this.isLoading = false;
            console.error('Error processing PDF URL:', err);
          }
          this.cdr.detectChanges();
        },
        error: (error) => {
          console.error('Error loading PDF document:', error);
          this.isLoading = false;
          this.cdr.detectChanges();
        }
      });
  }

  onSign(): void {
    if (!this.pdfUrlString) {
      console.error('No hay URL de PDF disponible para firmar');
      return;
    }

    this.isSigningInProgress = true;
    this.error = null;
    this.cdr.detectChanges();

    const firmaParams: FirmaDigitalParams = {
      location_url_pdf: this.pdfUrlString,
      location_logo: `${environment.BACKEND_URL_STORAGE}image_pdf_template/logo_grp.png`,
      post_location_upload: `${environment.BACKEND_URL}/api/document-signature`,
      asunto: `Firma de Parte Diario - ${this.data.date}`,
      rol: '',
      tipo: 'PARTE_DIARIO',
      status_position: '1',
      visible_position: false,
      bacht_operation: false,
      npaginas: 1,
      token: ''
    };

    // Llamar al servicio que abrirá la ventana popup
    console.log(firmaParams);
    
    this.signatureService.firmaDigital(firmaParams)
      .subscribe({
        next: (response) => {
          console.log('Firma digital exitosa. Respuesta:', response);
          this.isSigned = true;
          this.isSigningInProgress = false;
          this.signatureData = response;
          this.cdr.detectChanges();
          
          // Opcional: Mostrar mensaje de éxito
          alert('Documento firmado exitosamente');
        },
        error: (error) => {
          console.error('Error durante la firma digital:', error);
          this.isSigningInProgress = false;
          this.error = error;
          this.cdr.detectChanges();
          
          // Opcional: Mostrar mensaje de error
          if (error.includes('bloqueada')) {
            alert('Por favor, permita las ventanas emergentes para este sitio e intente nuevamente.');
          } else if (error.includes('cancelada') || error.includes('cerrada')) {
            console.log('Firma cancelada por el usuario');
          } else {
            alert('Error en el proceso de firma: ' + error);
          }
        }
      });
  }

  onCancel(): void {
    this.dialogRef.close(false);
  }

  onSave(): void {
    if (!this.isSigned) {
      alert('Debe firmar el documento antes de guardar');
      return;
    }

    console.log('Guardando firma para WorkLog ID:', this.data.workLogId);
    console.log('Datos de firma:', this.signatureData);
    
    // Cerrar el diálogo con resultado positivo
    this.dialogRef.close({
      signed: true,
      signatureData: this.signatureData
    });

    /* Implementar cuando tengas el endpoint listo
    this.dailyWorkLogService.saveSignature(this.data.workLogId, this.signatureData)
      .subscribe({
        next: (response) => {
          console.log('Firma guardada exitosamente:', response);
          this.dialogRef.close(true);
        },
        error: (error) => {
          console.error('Error al guardar la firma:', error);
          alert('Error al guardar la firma');
        }
      });
    */
  }

  onNoClick(): void {
    this.dialogRef.close(false);
  }

  onRetry(): void {
    this.loadPdfDocument();
  }
}