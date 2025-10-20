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
    MatInputModule
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
    private permissionService: PermissionService
  ) {
    this.userForm = this.fb.group({
      userId: ['', Validators.required],
      observation: ['']
    });
  }

  ngOnInit(): void {
    this.loadUsers();
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

  private loadUsers(): void {
    this.usersService.getUsers().subscribe({
      next: (users) => {
        this.users = users;
        this.filteredUsers = [...this.users];
        this.cdr.detectChanges();
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

  private getUserRelevantRoles(): string[] {
    const relevantRoles = ['Controlador_pd', 'Residente_pd', 'Supervisor_pd'];
    return relevantRoles.filter(role => this.permissionService.hasRole(role));
  }

  private getRoleToSignByDocumentState(): { roleId: number; roleName: string; statusPosition: string } | null {
    const userRelevantRoles = this.getUserRelevantRoles();
    
    console.log('Estado del documento:', this.documentState);
    console.log('Roles relevantes del usuario:', userRelevantRoles);

    switch (this.documentState) {
      case 0:
        if (userRelevantRoles.includes('Controlador_pd')) {
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
          this.cdr.detectChanges();
          this.loadPdfDocument();
        },
        error: (error) => {
          console.error('Error durante la firma digital:', error);
          this.isSigningInProgress = false;
          this.error = error;
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
    this.dialogRef.close(false);
  }

  onSend(): void {
    const selectedUser = this.userForm.get('userId')?.value as UserElement;
    const formSend = {
      userId: selectedUser.id,
      documentId: this.documentId
    }

    this.dailyWorkLogService.sendDocument(formSend)
      .subscribe({
        next: (response) => {
          this.dialogRef.close(true);
        },
        error: (error) => {
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

  isControllerAndCanSign(): boolean {
    return this.documentState === 0 && this.permissionService.hasRole('Controlador_pd');
  }
}
