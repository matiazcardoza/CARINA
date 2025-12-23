import { Component, Inject } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DailyWorkLogService } from '../../../../../services/DailyWorkLogService/daily-work-log-service';
import { FirmaDigitalParams, SignatureService } from '../../../../../services/SignatureService/signature-service';
import { environment } from '../../../../../../environments/environment';

import { FormBuilder, FormGroup, ReactiveFormsModule, FormControl, Validators } from '@angular/forms';
import { UsersService } from '../../../../../services/UsersService/users-service';
import { DocumentSignatureService, UserRoleElement } from '../../../../../services/DocumentSignatureService/document-signature-service';
import { startWith, map } from 'rxjs/operators';
import { MatAutocompleteModule } from '@angular/material/autocomplete';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';

import { UserElement } from '../../../users/users';
import { PermissionService } from '../../../../../services/AuthService/permission';

import { MatSnackBarModule } from '@angular/material/snack-bar';
import { MatSnackBar } from '@angular/material/snack-bar';
import { MatDialog } from '@angular/material/dialog';
import { AlertConfirm } from '../../../../../components/alert-confirm/alert-confirm';

export interface DocumentDailyPartElement {
  id: number;
  file_path: string;
  state: number;
  pages?: number;
}

interface DialogData {
  documentId: number;
}

@Component({
  selector: 'app-daily-work-signature',
  standalone: true,
  imports: [
    CommonModule,
    MatIconModule,
    MatButtonModule,
    MatToolbarModule,
    MatProgressSpinnerModule,
    ReactiveFormsModule,
    MatAutocompleteModule,
    MatFormFieldModule,
    MatInputModule,
    MatSnackBarModule
  ],
  templateUrl: './document-signature.html',
  styleUrl: './document-signature.css'
})
export class DocumentSignature {

  pdfUrl: SafeResourceUrl | null = null;
  pdfUrlString: string = '';
  documentId: number | null = null;
  isLoading = false;
  error = null;
  isSigned = false;
  signatureData: any = null;
  isSigningInProgress = false;
  numberOfPages: number = 0;
  shouldAutoSend = false;

  userForm: FormGroup;
  users: UserElement[] = [];
  filteredUsers: UserElement[] = [];

  userRoles: string[] = [];
  documentState: number = 0;

  showReturnSection: boolean = false;
  returnObservation: string = '';

  private readonly ROLE_MAPPING = {
    'Controlador_pd': { id: 3, name: 'CONTROLADOR', statusPosition: '1' },
    'Residente_pd': { id: 4, name: 'RESIDENTE', statusPosition: '2' },
    'Supervisor_pd': { id: 5, name: 'SUPERVISOR', statusPosition: '3' }
  };

  constructor(
    public dialogRef: MatDialogRef<DocumentSignature>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData,
    private cdr: ChangeDetectorRef,
    private dailyWorkLogService: DailyWorkLogService,
    private signatureService: SignatureService,
    private sanitizer: DomSanitizer,
    private fb: FormBuilder,
    private usersService: UsersService,
    private documentSignatureService: DocumentSignatureService,
    private permissionService: PermissionService,
    private snackBar: MatSnackBar,
    private dialog: MatDialog
  ) {
    this.userForm = this.fb.group({
      userId: ['', Validators.required],
      observation: ['']
    });
  }

  ngOnInit(): void {
    this.loadUserRoles();
    this.loadPdfDocument();
  }

  private loadUserRoles(): void {
    this.permissionService.roles$.subscribe({
      next: (roles) => {
        this.userRoles = roles;
        console.log('Roles del usuario cargados:', this.userRoles);
        this.cdr.detectChanges();
      },
      error: (error) => {
        console.error('Error al cargar roles del usuario:', error);
      }
    });
  }

  private isUserObjectSelected(): boolean {
    const value = this.userForm.get('userId')?.value;
    return value && typeof value === 'object' && value.id !== undefined;
  }

  private loadUsers(): void {
    const documentState = this.documentState;
      if (documentState === 3) {
      console.log('Documento en estado final, no se cargan usuarios para envío');
      return;
    }

    // Solo cargar usuarios si el estado actual requiere envío:
    // Estado 1: Controlador envía a Residente
    // Estado 2: Residente envía a Supervisor
    const requiresSending = documentState === 1 || documentState === 2;

    if (!requiresSending) {
      console.log('Estado actual no requiere envío de documento');
      return;
    }
    this.usersService.getUsersSelected(documentState).subscribe({
      next: (users) => {
        this.users = users;
        this.filteredUsers = [...this.users];
        if (this.users.length > 0 && !this.userForm.get('userId')?.value) {
          this.userForm.get('userId')?.setValue(this.users[0]);
        }
        this.userForm.get('userId')?.markAsUntouched();
        this.cdr.detectChanges();

        if (this.isSigned && this.shouldAutoSend) {
          this.handleAutoSend();
          this.shouldAutoSend = false;
        }
      },
      error: (error) => {
        console.error('Error al cargar usuarios:', error);
      }
    });
    this.userForm.get('userId')?.valueChanges.pipe(
      startWith(''),
      map(value => (typeof value === 'string' ? value : value?.name))
    ).subscribe(name => {
      this.filteredUsers = this._filterUsers(name || '');
    });
  }

  private handleAutoSend(): void {
    if (this.users.length === 1) {
      this.userForm.get('userId')?.setValue(this.users[0]);
      this.onSendConfirm();
      return;
    }

    const selected = this.userForm.get('userId')?.value;

    if (!selected || typeof selected !== 'object') {
      return;
    }

    const dialogRef = this.dialog.open(AlertConfirm, {
      width: '450px',
      data: {
        title: 'Confirmar envío de documento',
        message: '¿Está seguro de enviar este documento a:',
        content: `${selected.persona_name} ${selected.last_name} (${selected.num_doc})`,
        confirmText: 'Enviar',
        cancelText: 'Cancelar'
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.onSendConfirm();
      } else {
        this.userForm.get('userId')?.setValue('');
        this.shouldAutoSend = false;
      }
    });
  }

  displayUser(user: UserElement | null): string {
    return user ? `${user.persona_name} ${user.last_name} ${user.num_doc} - ${user.email}` : '';
  }

  private _filterUsers(value: string): UserElement[] {
    const filterValue = value.toLowerCase();
    return this.users.filter(user =>
      user.persona_name.toLowerCase().includes(filterValue) ||
      user.last_name.toLowerCase().includes(filterValue) ||
      user.num_doc.toLowerCase().includes(filterValue) ||
      user.email.toLowerCase().includes(filterValue)
    );
  }

  hasResidentRole(): boolean {
    return this.permissionService.hasRole('Residente_pd');
  }

  isController(): boolean {
    return this.permissionService.hasRole('Controlador_pd');
  }

  isSupervisor(): boolean {
    return this.permissionService.hasRole('Supervisor_pd');
  }

  toggleReturnSection(): void {
    this.showReturnSection = !this.showReturnSection;
    if (!this.showReturnSection) {
      this.userForm.get('observation')?.setValue('');
    }
  }

  onUserSelected(user: UserElement): void {
    this.userForm.get('userId')?.setValue(user);
  }

  loadPdfDocument(): void {
    this.isLoading = true;
    this.error = null;
    this.pdfUrl = null;
    this.cdr.detectChanges();

    const documentId = this.data.documentId;

    this.documentSignatureService.getWorkLogDocumentSignature(documentId)
      .subscribe({
        next: (data: DocumentDailyPartElement) => {
          try {
            const pdfPath = data.file_path;
            const document_id = data.id;
            this.documentId = document_id;
            this.documentState = data.state || 0;
            this.numberOfPages = data.pages || 0;
            console.log('Estado del documento:', this.documentState);
            if (this.shouldLoadUsersForCurrentState()) {
              this.loadUsers();
            }
            const fullPdfUrl = `${environment.BACKEND_URL_STORAGE}${pdfPath}?timestamp=${new Date().getTime()}`;
            this.pdfUrlString = fullPdfUrl;
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

  private shouldLoadUsersForCurrentState(): boolean {
    const userRelevantRoles = this.getUserRelevantRoles();
    if (this.documentState === 0) {
      return false;
    }
    if (this.documentState === 3) {
      return false;
    }
    if (this.documentState === 1) {
      if (this.hasOnlyControllerRole()) {
        return true;
      }
      if (userRelevantRoles.includes('Residente_pd') && !this.isSigned) {
        return false;
      }
      if (userRelevantRoles.includes('Residente_pd') && this.isSigned) {
        return true;
      }
      if (userRelevantRoles.includes('Supervisor_pd')) {
        return true;
      }
    }
    if (this.documentState === 2) {
      if (userRelevantRoles.includes('Residente_pd')) {
        return true;
      }
      if (userRelevantRoles.includes('Supervisor_pd') && !this.isSigned) {
        return false;
      }
      if (userRelevantRoles.includes('Supervisor_pd') && this.isSigned) {
        return true;
      }
    }
    return false;
  }

  private hasOnlyControllerRole(): boolean {
    const userRelevantRoles = this.getUserRelevantRoles();
    return userRelevantRoles.length === 1 && 
          userRelevantRoles.includes('Controlador_pd');
  }

  private getUserRelevantRoles(): string[] {
    const relevantRoles = ['Controlador_pd', 'Residente_pd', 'Supervisor_pd'];
    return relevantRoles.filter(role => this.permissionService.hasRole(role));
  }

  private getRoleToSignByDocumentState(): { roleId: number; roleName: string; statusPosition: string } | null {
    const userRelevantRoles = this.getUserRelevantRoles();

    console.log('Estado del documento:', this.documentState);
    console.log('Roles relevantes del usuario:', userRelevantRoles);

    const hasBothSupervisorAndController =
    userRelevantRoles.includes('Supervisor_pd') &&
    userRelevantRoles.includes('Controlador_pd');

    const hasBothResidenteAndController =
    userRelevantRoles.includes('Residente_pd') &&
    userRelevantRoles.includes('Controlador_pd');

    switch (this.documentState) {
      case 0:
        if (hasBothSupervisorAndController) {
          return {
            roleId: this.ROLE_MAPPING['Supervisor_pd'].id,
            roleName: this.ROLE_MAPPING['Supervisor_pd'].name,
            statusPosition: this.ROLE_MAPPING['Supervisor_pd'].statusPosition
          };
        } else if (hasBothResidenteAndController){
          return {
            roleId: this.ROLE_MAPPING['Residente_pd'].id,
            roleName: this.ROLE_MAPPING['Residente_pd'].name,
            statusPosition: this.ROLE_MAPPING['Residente_pd'].statusPosition
          };
        }else if (userRelevantRoles.includes('Controlador_pd')) {
          return {
            roleId: this.ROLE_MAPPING['Controlador_pd'].id,
            roleName: this.ROLE_MAPPING['Controlador_pd'].name,
            statusPosition: this.ROLE_MAPPING['Controlador_pd'].statusPosition
          };
        }
        break;

      case 1:
        if (userRelevantRoles.includes('Residente_pd')) {
          return {
            roleId: this.ROLE_MAPPING['Residente_pd'].id,
            roleName: this.ROLE_MAPPING['Residente_pd'].name,
            statusPosition: this.ROLE_MAPPING['Residente_pd'].statusPosition
          };
        } else if (userRelevantRoles.includes('Supervisor_pd')) {
          return {
            roleId: this.ROLE_MAPPING['Supervisor_pd'].id,
            roleName: this.ROLE_MAPPING['Supervisor_pd'].name,
            statusPosition: this.ROLE_MAPPING['Supervisor_pd'].statusPosition
          };
        }
        break;

      case 2:
        if (userRelevantRoles.includes('Supervisor_pd')) {
          return {
            roleId: this.ROLE_MAPPING['Supervisor_pd'].id,
            roleName: this.ROLE_MAPPING['Supervisor_pd'].name,
            statusPosition: this.ROLE_MAPPING['Supervisor_pd'].statusPosition
          };
        }
        break;

      default:
        console.warn('Estado de documento no reconocido:', this.documentState);
        break;
    }

    return null;
  }

  canUserSign(): boolean {
    const roleToSign = this.getRoleToSignByDocumentState();

    if (!roleToSign) {
      console.log('El usuario no puede firmar en este estado');
      return false;
    }

    console.log('El usuario puede firmar con:', roleToSign);
    return true;
  }

  onSign(): void {
    if (!this.pdfUrlString) {
      console.error('No hay URL de PDF disponible para firmar');
      return;
    }

    const roleToSign = this.getRoleToSignByDocumentState();

    if (!roleToSign) {
      alert('No tienes permiso para firmar en este estado del documento');
      return;
    }

    this.isSigningInProgress = true;
    this.error = null;
    this.shouldAutoSend = true;
    this.cdr.detectChanges();

    const firmaParams: FirmaDigitalParams = {
      location_url_pdf: this.pdfUrlString,
      location_logo: `${environment.BACKEND_URL_STORAGE}image_pdf_template/logo_firma_digital.png`,
      post_location_upload: `${environment.BACKEND_URL}/api/signature-document/${this.documentId}/${roleToSign.roleId}`,
      asunto: `Firma de Parte Diario`,
      rol: roleToSign.roleName,
      tipo: 'daily_parts',
      status_position: roleToSign.statusPosition,
      visible_position: false,
      bacht_operation: false,
      npaginas: this.numberOfPages,
      token: ''
    };

    this.signatureService.firmaDigital(firmaParams)
      .subscribe({
        next: (response) => {
          console.log('Firma digital exitosa. Respuesta:', response);
          this.isSigned = true;
          this.isSigningInProgress = false;
          this.signatureData = response;
          this.loadUsers();
          this.cdr.detectChanges();
          this.loadPdfDocument();
        },
        error: (error) => {
          console.error('Error durante la firma digital:', error);
          this.isSigningInProgress = false;
          this.error = error;
          this.shouldAutoSend = false;
          this.cdr.detectChanges();

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
    if (this.isSigned) {
      this.dialogRef.close({
        success: true,
        signed: true,
        message: 'Documento firmado correctamente'
      });
    } else {
      this.dialogRef.close(false);
    }
  }

  onSend(): void {
    const selected = this.userForm.get('userId')?.value;
    if (!this.isUserObjectSelected()) {
      this.snackBar.open('Debe seleccionar un usuario de la lista', 'Cerrar', {
        duration: 4000,
        horizontalPosition: 'end',
        verticalPosition: 'top',
        panelClass: ['warning-snackbar']
      });
      this.userForm.get('userId')?.setValue('');
      return;
    }

    if (this.users.length > 1) {
      const dialogRef = this.dialog.open(AlertConfirm, {
        width: '450px',
        data: {
          title: 'Confirmar envío de documento',
          message: '¿Está seguro de enviar este documento a:',
          content: `${selected.persona_name} ${selected.last_name} (${selected.num_doc})`,
          confirmText: 'Enviar',
          cancelText: 'Cancelar'
        }
      });

      dialogRef.afterClosed().subscribe(result => {
        if (result) {
          this.onSendConfirm();
        }
      });

      return;
    }
    this.onSendConfirm();
  }

  private onSendConfirm(): void {
    const selectedUser = this.userForm.get('userId')?.value as UserElement;

    const formSend = {
      userId: selectedUser.id,
      documentId: this.documentId
    };

    this.dailyWorkLogService.sendDocument(formSend)
      .subscribe({
        next: (response) => {
        this.dialogRef.close({
          success: true,
          message: response.message
        });
      },
      error: (error) => {
        this.snackBar.open(
          'Error al enviar el documento',
          'Cerrar',
          {
            duration: 4000,
            horizontalPosition: 'end',
            verticalPosition: 'top',
            panelClass: ['error-snackbar']
          }
        );
      }
    });
  }

  onReturnToController(): void {
    const observation = this.userForm.get('observation')?.value;
    const formReturn = {
      documentId: this.documentId,
      observation: observation.trim()
    };

    this.documentSignatureService.resendDocumentToController(formReturn)
      .subscribe({
        next: (response) => {
          this.dialogRef.close(true);
        },
        error: (error) => {
        }
    });
  }

  onNoClick(): void {
    this.dialogRef.close(false);
  }

  onRetry(): void {
    this.loadPdfDocument();
  }

  shouldShowSignButton(): boolean {
    if (this.isSigned) {
      return false;
    }
    const userRelevantRoles = this.getUserRelevantRoles();
    if (this.documentState === 0 && userRelevantRoles.includes('Controlador_pd')) {
      return true;
    }
    if (this.documentState === 1 && userRelevantRoles.includes('Residente_pd')) {
      return true;
    }
    if ([0, 1, 2].includes(this.documentState) && userRelevantRoles.includes('Supervisor_pd')) {
      return true;
    }
    return false;
  }

  clearUserSelection(event: Event): void {
    event.stopPropagation();
    this.userForm.get('userId')?.setValue('');
    this.userForm.get('userId')?.markAsUntouched();
  }

  onSignPassword(): void {
    this.isSigningInProgress = true;
    this.shouldAutoSend = true;
    this.cdr.detectChanges();

    const documentId = this.documentId;

    this.signatureService.signWithPassword(documentId).subscribe({
      next: (response) => {
        if (response.correcto) {
          this.snackBar.open('Documento firmado exitosamente', 'Cerrar', {
            duration: 4000,
            horizontalPosition: 'end',
            verticalPosition: 'top',
            panelClass: ['success-snackbar']
          });

          this.isSigned = true;
          this.isSigningInProgress = false;
          this.loadUsers();
          this.cdr.detectChanges();

          // Recargar el documento
          this.loadPdfDocument();
        } else {
          this.snackBar.open(response.mensaje, 'Cerrar', {
            duration: 4000,
            horizontalPosition: 'end',
            verticalPosition: 'top',
            panelClass: ['error-snackbar']
          });
          this.isSigningInProgress = false;
          this.shouldAutoSend = false;
        }
      },
      error: (error) => {
        console.error('Error al firmar:', error);
        const mensaje = error.error?.mensaje || 'Error al firmar el documento';

        this.snackBar.open(mensaje, 'Cerrar', {
          duration: 4000,
          horizontalPosition: 'end',
          verticalPosition: 'top',
          panelClass: ['error-snackbar']
        });

        this.isSigningInProgress = false;
        this.shouldAutoSend = false;
        this.cdr.detectChanges();
      }
    });
  }

  canControllerSignWithPassword(): boolean {
    const userRelevantRoles = this.getUserRelevantRoles();

    return userRelevantRoles.includes('Controlador_pd') &&
          !userRelevantRoles.includes('Residente_pd') &&
          !userRelevantRoles.includes('Supervisor_pd') &&
          this.canUserSign() &&
          !this.isSigned;
  }
}
