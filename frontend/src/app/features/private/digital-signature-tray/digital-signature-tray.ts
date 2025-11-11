import { AfterViewInit, Component, ViewChild, OnInit, inject, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTableDataSource, MatTableModule } from '@angular/material/table';
import { MatPaginator, MatPaginatorModule } from '@angular/material/paginator';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatTooltipModule } from '@angular/material/tooltip';

import { DocumentSignatureService } from '../../../services/DocumentSignatureService/document-signature-service';
import { DocumentSignature } from './form/document-signature/document-signature';
import { MassiveDocumentSignature } from './form/massive-document-signature/massive-document-signature';

import { HasPermissionDirective } from '../../../shared/directives/permission.directive';
import { HasRoleDirective } from '../../../shared/directives/permission.directive';

export interface DocumentSignatureUserElement {
  id: number;
  description: string;
  goal_detail: string;
  last_work_date: string;
  file_path: string;
  observation: string;
  state: number;
  pages?: number;
}

@Component({
  selector: 'app-digital-signature-tray',
  standalone: true,
  imports: [
    CommonModule,
    MatButtonModule,
    MatIconModule,
    MatTableModule,
    MatPaginatorModule,
    MatDialogModule,
    MatTooltipModule,
    HasPermissionDirective,
    HasRoleDirective
  ],
  templateUrl: './digital-signature-tray.html',
  styleUrl: './digital-signature-tray.css'
})
export class DigitalSignatureTray implements AfterViewInit, OnInit {

  displayedColumns: string[] = ['id', 'description','state', 'last_work_date', 'actions'];
  dataSource = new MatTableDataSource<DocumentSignatureUserElement>([]);

  private documentSignatureService = inject(DocumentSignatureService);
  private dialog = inject(MatDialog);
  private cdr = inject(ChangeDetectorRef);

  isLoading = false;
  error: string | null = null;

  @ViewChild(MatPaginator) paginator!: MatPaginator;

  ngOnInit() {
    this.loadDocumentsData();
  }

  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
  }

  loadDocumentsData(): void {
    this.isLoading = true;
    this.error = null;
    this.documentSignatureService.getPendingDocuments()
      .subscribe({
        next: (data) => {
          console.log('Documentos pendientes cargados:', data);
          this.dataSource.data = data;
          this.isLoading = false;
          this.cdr.detectChanges();
        },
        error: (err) => {
          this.error = 'Error al cargar los documentos pendientes. Por favor, intenta nuevamente.';
          this.isLoading = false;
          this.cdr.detectChanges();
        }
      });
  }

  reloadData() {
    this.loadDocumentsData();
  }

  openSignDialog(id: number) {
    const dialogRef = this.dialog.open(DocumentSignature, {
      width: '100vw',
      height: '100vh',
      maxWidth: '100vw',
      maxHeight: '100vh',
      panelClass: ['maximized-dialog-panel', 'no-scroll-dialog'],
      disableClose: false,
      hasBackdrop: true,
      backdropClass: 'maximized-dialog-backdrop',
      autoFocus: false,
      restoreFocus: false,
      data: {
        documentId: id
      }
    });
  
    setTimeout(() => {
      const body = document.body;
      const html = document.documentElement;
      body.style.overflow = 'hidden';
      html.style.overflow = 'hidden';
    }, 0);
  
    dialogRef.afterClosed().subscribe(result => {
      const body = document.body;
      const html = document.documentElement;
      body.style.overflow = '';
      html.style.overflow = '';
      this.reloadData();
      this.cdr.detectChanges();
    });
  }

  // NUEVO MÉTODO: Abrir diálogo de firma masiva
  openMassiveSignDialog() {
    // Verificar que haya documentos
    if (this.dataSource.data.length === 0) {
      alert('No hay documentos disponibles para firma masiva');
      return;
    }

    console.log('datos enviados a componente', this.dataSource.data);

    const dialogRef = this.dialog.open(MassiveDocumentSignature, {
      width: '100vw',
      height: '100vh',
      maxWidth: '100vw',
      maxHeight: '100vh',
      panelClass: ['maximized-dialog-panel', 'no-scroll-dialog'],
      disableClose: false,
      hasBackdrop: true,
      backdropClass: 'maximized-dialog-backdrop',
      autoFocus: false,
      restoreFocus: false,
      data: {
        documents: this.dataSource.data
      }
    });

    setTimeout(() => {
      const body = document.body;
      const html = document.documentElement;
      body.style.overflow = 'hidden';
      html.style.overflow = 'hidden';
    }, 0);

    dialogRef.afterClosed().subscribe(result => {
      const body = document.body;
      const html = document.documentElement;
      body.style.overflow = '';
      html.style.overflow = '';
      
      if (result && result.signed) {
        console.log(`Firma masiva completada: ${result.signedCount} firmados, ${result.errorCount} errores`);
      }
      
      this.reloadData();
      this.cdr.detectChanges();
    });
  }
}