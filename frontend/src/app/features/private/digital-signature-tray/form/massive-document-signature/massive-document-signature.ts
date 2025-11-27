import { Component, Inject, OnInit, ChangeDetectorRef } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatListModule } from '@angular/material/list';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { CommonModule } from '@angular/common';
import { DomSanitizer } from '@angular/platform-browser';
import { forkJoin, of, concat } from 'rxjs';
import { catchError, tap, delay } from 'rxjs/operators';

import { DocumentSignatureService } from '../../../../../services/DocumentSignatureService/document-signature-service';
import { SignatureService, FirmaDigitalParams } from '../../../../../services/SignatureService/signature-service';
import { PermissionService } from '../../../../../services/AuthService/permission';
import { environment } from '../../../../../../environments/environment';

export interface DocumentToSign {
  id: number;
  description: string;
  goal_detail: string;
  last_work_date: string;
  file_path: string;
  state: number;
  pages?: number;
  selected: boolean;
  signatureStatus?: 'pending' | 'signing' | 'success' | 'error';
  errorMessage?: string;
}

interface DialogData {
  documents: DocumentToSign[];
}

interface RoleStateOption {
  role: string;
  roleName: string;
  state: number;
  roleId: number;
  statusPosition: string;
  documentCount: number;
  displayName: string;
}

@Component({
  selector: 'app-massive-document-signature',
  standalone: true,
  imports: [
    CommonModule,
    MatIconModule,
    MatButtonModule,
    MatToolbarModule,
    MatProgressSpinnerModule,
    MatProgressBarModule,
    MatListModule,
    MatCheckboxModule
  ],
  templateUrl: './massive-document-signature.html',
  styleUrl: './massive-document-signature.css'
})
export class MassiveDocumentSignature implements OnInit {

  documents: DocumentToSign[] = [];
  isLoading = false;
  isSigningInProgress = false;
  currentSigningIndex = 0;
  totalDocuments = 0;
  signedDocuments = 0;
  errorDocuments = 0;

  availableRoleStateOptions: RoleStateOption[] = [];
  selectedRoleState: RoleStateOption | null = null;
  filteredDocuments: DocumentToSign[] = [];
  
  private readonly ROLE_MAPPING = {
    'Controlador_pd': { 
      id: 3, 
      name: 'CONTROLADOR', 
      statusPosition: '1',
      documentStates: [0]
    },
    'Residente_pd': { 
      id: 4, 
      name: 'RESIDENTE', 
      statusPosition: '2',
      documentStates: [0, 1]
    },
    'Supervisor_pd': { 
      id: 5, 
      name: 'SUPERVISOR', 
      statusPosition: '3',
      documentStates: [0, 1, 2]
    }
  };

  constructor(
    public dialogRef: MatDialogRef<MassiveDocumentSignature>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData,
    private cdr: ChangeDetectorRef,
    private documentSignatureService: DocumentSignatureService,
    private signatureService: SignatureService,
    private sanitizer: DomSanitizer,
    private permissionService: PermissionService
  ) {}

  ngOnInit(): void {
    this.createRoleStateOptions();
    this.documents = this.data.documents.map(doc => ({
      ...doc,
      selected: this.canSignDocument(doc.state),
      signatureStatus: 'pending'
    }));
    this.autoSelectRoleState();
  }

  private createRoleStateOptions(): void {
    const userRoles = this.getUserRelevantRoles();
    this.availableRoleStateOptions = [];

    userRoles.forEach(role => {
      const roleConfig = this.ROLE_MAPPING[role as keyof typeof this.ROLE_MAPPING];
      if (!roleConfig) return;

      // Crear una opción por cada estado que el rol puede firmar
      roleConfig.documentStates.forEach(state => {
        // Contar documentos disponibles para este rol+estado
        const documentCount = this.data.documents.filter(doc => doc.state === state).length;
        
        if (documentCount > 0) {
          this.availableRoleStateOptions.push({
            role: role,
            roleName: roleConfig.name,
            state: state,
            roleId: roleConfig.id,
            statusPosition: roleConfig.statusPosition,
            documentCount: documentCount,
            displayName: `${roleConfig.name} - Estado ${state}`
          });
        }
      });
    });

    console.log('Opciones de rol+estado disponibles:', this.availableRoleStateOptions);
  }

  private autoSelectRoleState(): void {
    // Si ya hay una selección, mantenerla
    if (this.selectedRoleState) {
      this.onRoleStateChange();
      return;
    }

    // Si solo hay una opción, seleccionarla automáticamente
    if (this.availableRoleStateOptions.length === 1) {
      this.selectedRoleState = this.availableRoleStateOptions[0];
      this.onRoleStateChange();
      return;
    }

    // Si hay múltiples opciones, seleccionar la que tenga más documentos
    if (this.availableRoleStateOptions.length > 1) {
      const bestOption = this.availableRoleStateOptions.reduce((prev, current) => 
        current.documentCount > prev.documentCount ? current : prev
      );
      this.selectedRoleState = bestOption;
      this.onRoleStateChange();
    }
  }

  onRoleStateChange(): void {
    if (!this.selectedRoleState) {
      this.filteredDocuments = [];
      this.documents.forEach(doc => doc.selected = false);
      this.totalDocuments = 0;
      this.cdr.detectChanges();
      return;
    }

    // Filtrar documentos solo del estado específico seleccionado
    this.documents.forEach(doc => {
      doc.selected = doc.state === this.selectedRoleState!.state;
    });
    
    this.filteredDocuments = this.documents.filter(doc => doc.state === this.selectedRoleState!.state);
    this.totalDocuments = this.filteredDocuments.filter(d => d.selected).length;
    this.cdr.detectChanges();
  }

  getRoleDisplayName(role: string): string {
    return this.ROLE_MAPPING[role as keyof typeof this.ROLE_MAPPING]?.name || role;
  }

  getDocumentStatesForRole(role: string): number[] {
    return this.ROLE_MAPPING[role as keyof typeof this.ROLE_MAPPING]?.documentStates || [];
  }

  get selectedDocuments(): DocumentToSign[] {
    return this.filteredDocuments.filter(d => d.selected);
  }

  get progressPercentage(): number {
    if (this.totalDocuments === 0) return 0;
    return ((this.signedDocuments + this.errorDocuments) / this.totalDocuments) * 100;
  }

  toggleDocumentSelection(document: DocumentToSign): void {
    if (!this.isSigningInProgress && this.selectedRoleState && this.canSignDocument(document.state)) {
      document.selected = !document.selected;
      this.totalDocuments = this.selectedDocuments.length;
      this.cdr.detectChanges();
    }
  }

  selectAll(): void {
    if (this.isSigningInProgress || !this.selectedRoleState) return;
    
    const allSelected = this.filteredDocuments.every(d => d.selected);
    
    this.filteredDocuments.forEach(doc => {
      doc.selected = !allSelected;
    });
    
    this.totalDocuments = this.selectedDocuments.length;
    this.cdr.detectChanges();
  }

  canSignDocument(state: number): boolean {
    if (!this.selectedRoleState) return false;
    return state === this.selectedRoleState.state;
  }

  private getUserRelevantRoles(): string[] {
    const relevantRoles = ['Controlador_pd', 'Residente_pd', 'Supervisor_pd'];
    const userRoles = relevantRoles.filter(role => this.permissionService.hasRole(role));
    
    // Aplicar prioridad: si tiene múltiples roles, priorizar en orden jerárquico
    if (userRoles.length > 1) {
      // Prioridad: Supervisor > Residente > Controlador
      if (userRoles.includes('Supervisor_pd')) {
        return ['Supervisor_pd'];
      }
      if (userRoles.includes('Residente_pd')) {
        return ['Residente_pd'];
      }
    }
    
    return userRoles;
  }

  private getRoleToSign(): { roleId: number; roleName: string; statusPosition: string } | null {
    if (!this.selectedRoleState) {
      console.error('No hay rol+estado seleccionado');
      return null;
    }

    return {
      roleId: this.selectedRoleState.roleId,
      roleName: this.selectedRoleState.roleName,
      statusPosition: this.selectedRoleState.statusPosition
    };
  }

  async onSignMassive(): Promise<void> {
    if (!this.selectedRoleState) {
      alert('Por favor, selecciona un rol antes de firmar');
      return;
    }

    const documentsToSign = this.selectedDocuments;
    
    if (documentsToSign.length === 0) {
      alert('Por favor, selecciona al menos un documento para firmar');
      return;
    }

    this.isSigningInProgress = true;
    this.signedDocuments = 0;
    this.errorDocuments = 0;
    this.currentSigningIndex = 1;
    this.cdr.detectChanges();

    try {
      const documentIds = documentsToSign.map(d => d.id);
      const batchData = await this.documentSignatureService
        .prepareMassiveSignature(documentIds)
        .toPromise();

      if (!batchData) {
        throw new Error('No se pudo preparar el lote de documentos');
      }

      const roleToSign = this.getRoleToSign();
      
      if (!roleToSign) {
        throw new Error('No tienes permiso para firmar estos documentos');
      }

      documentsToSign.forEach(doc => {
        doc.signatureStatus = 'signing';
      });
      this.cdr.detectChanges();
      console.log('status position', roleToSign.statusPosition);
      const firmaParams: FirmaDigitalParams = {
        location_url_pdf: batchData.zip_url,
        location_logo: `${environment.BACKEND_URL_STORAGE}image_pdf_template/logo_firma_digital.png`,
        post_location_upload: `${environment.BACKEND_URL}/api/signature-document/process-massive/${batchData.batch_id}/${roleToSign.roleId}`,
        asunto: `Firma de Parte Diario-operación masiva`,
        rol: roleToSign.roleName,
        tipo: 'daily_parts',
        status_position: roleToSign.statusPosition,
        visible_position: false,
        bacht_operation: true,
        npaginas: 1,
        token: ''
      };

      console.log('Iniciando firma masiva con parámetros:', firmaParams);

      const signatureResponse = await this.signatureService.firmaDigital(firmaParams).toPromise();

      console.log('Respuesta de firma digital:', signatureResponse);
      await new Promise(resolve => setTimeout(resolve, 2000));

      documentsToSign.forEach(doc => {
        doc.signatureStatus = 'success';
        doc.state = doc.state + 1;
        doc.selected = false;
      });
      this.signedDocuments = documentsToSign.length;
      this.onRoleStateChange();
    } catch (error: any) {
      console.error('Error en firma masiva:', error);
      
      documentsToSign.forEach(doc => {
        doc.signatureStatus = 'error';
        doc.errorMessage = error.message || 'Error en firma masiva';
      });
      this.errorDocuments = documentsToSign.length;

    } finally {
      this.isSigningInProgress = false;
      this.cdr.detectChanges();

      const message = `Proceso completado:\n` +
        `✓ Firmados: ${this.signedDocuments}\n` +
        `✗ Errores: ${this.errorDocuments}`;
      alert(message);
    }
  }

  getDocumentStateLabel(state: number): string {
    switch (state) {
      case 0: return 'PENDIENTE DE FIRMA';
      case 1: return 'FIRMADO POR CONTROLADOR';
      case 2: return 'FIRMADO POR RESIDENTE';
      case 3: return 'FIRMADO POR SUPERVISOR';
      default: return 'ESTADO DESCONOCIDO';
    }
  }

  getStatusIcon(status?: string): string {
    switch (status) {
      case 'pending': return 'schedule';
      case 'signing': return 'hourglass_empty';
      case 'success': return 'check_circle';
      case 'error': return 'error';
      default: return 'schedule';
    }
  }

  getStatusClass(status?: string): string {
    switch (status) {
      case 'pending': return 'status-pending';
      case 'signing': return 'status-signing';
      case 'success': return 'status-success';
      case 'error': return 'status-error';
      default: return 'status-pending';
    }
  }

  onCancel(): void {
    if (this.isSigningInProgress) {
      const confirm = window.confirm('Hay una firma en proceso. ¿Estás seguro de cancelar?');
      if (!confirm) return;
    }
    
    this.dialogRef.close({
      signed: this.signedDocuments > 0,
      signedCount: this.signedDocuments,
      errorCount: this.errorDocuments
    });
  }
}