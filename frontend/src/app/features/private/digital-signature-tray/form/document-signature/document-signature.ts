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

  role: UserRoleElement[] = [];
  documentState: number = 0;

  showReturnSection: boolean = false;
  returnObservation: string = '';

  constructor(
    public dialogRef: MatDialogRef<DocumentSignature>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData,
    private cdr: ChangeDetectorRef,
    private dailyWorkLogService: DailyWorkLogService,
    private signatureService: SignatureService,
    private sanitizer: DomSanitizer,
    private fb: FormBuilder,
    private usersService: UsersService,
    private documentSignatureService: DocumentSignatureService
  ) {
    this.userForm = this.fb.group({
      userId: ['', Validators.required],
      observation: ['']
    });
  }

  ngOnInit(): void {
    this.loadUsers();
    this.loadRole();
    this.loadPdfDocument();
  }

  private loadRole(){
    this.documentSignatureService.getRole().subscribe({
      next: (role) => {
        console.log('estos son los roles del usuario: ', role);
        this.role = role;
        this.cdr.detectChanges();
      },
      error: (error) => {
        console.error('Error al cargar usuarios:', error);
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
    return this.role.some(r => r.id === 4);
  }

  isController(): boolean {
    return this.role.some(r => r.id === 3);
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

    this.documentSignatureService.getWorkLogDocument(documentId)
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

  private roleIdToName = new Map<number, string>([
    [3, 'CONTROLADOR'],
    [4, 'RESIDENTE'],
    [5, 'SUPERVISOR']
  ]);

  private getRoleToSignByDocumentState(): { roleId: number | null, statusPosition: string } {
    const userRoleIds = this.role.map(r => r.id);

    switch (this.documentState) {
      case 0:
        if (userRoleIds.includes(3)) {
          return { roleId: 3, statusPosition: '1' };
        }
        break;

      case 1:
        if (userRoleIds.includes(4)) {
          return { roleId: 4, statusPosition: '2' };
        }
        if (userRoleIds.includes(5) && !userRoleIds.includes(4)) {
          return { roleId: 5, statusPosition: '2' };
        }
        break;

      case 2:
        if (userRoleIds.includes(5)) {
          return { roleId: 5, statusPosition: '3' };
        }
        break;

      default:
        console.warn('Estado de documento no reconocido:', this.documentState);
        break;
    }
    return { roleId: null, statusPosition: '1' };
  }

  private getStatusPositionByDocumentState(): string {
    const result = this.getRoleToSignByDocumentState();
    return result.statusPosition;
  }

  private getRoleNameByDocumentState(): string {
    const result = this.getRoleToSignByDocumentState();

    if (result.roleId === null) {
      return 'ADMIN';
    }

    if (result.roleId === 5 && this.documentState === 1) {
      return 'RESIDENTE';
    }

    return this.roleIdToName.get(result.roleId) || 'ADMIN';
  }

  onSign(): void {
    if (!this.pdfUrlString) {
      console.error('No hay URL de PDF disponible para firmar');
      return;
    }

    this.isSigningInProgress = true;
    this.error = null;
    this.cdr.detectChanges();

    const statusPosition = this.getStatusPositionByDocumentState();
    const cargo = this.getRoleNameByDocumentState();

    const firmaParams: FirmaDigitalParams = {
      location_url_pdf: this.pdfUrlString,
      location_logo: `${environment.BACKEND_URL_STORAGE}image_pdf_template/logo_firma_digital.png`,
      post_location_upload: `${environment.BACKEND_URL}/api/document-signature/${this.documentId}`,
      asunto: `Firma de Parte Diario`,
      rol: cargo,
      tipo: 'daily_parts',
      status_position: statusPosition,
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

  /*onReturnToController(): void {
    const observation = this.userForm.get('observation')?.value;

    if (!observation || observation.trim() === '') {
      alert('Debe ingresar una observación para devolver el documento');
      return;
    }

    const formReturn = {
      documentId: this.documentId,
      observation: observation
    };

    // Aquí debes crear el método en tu servicio
    this.dailyWorkLogService.returnDocumentToController(formReturn)
      .subscribe({
        next: (response) => {
          console.log('Documento devuelto exitosamente:', response);
          this.dialogRef.close(true);
        },
        error: (error) => {
          console.error('Error al devolver documento:', error);
          alert('Error al devolver el documento');
        }
    });
  }*/

  onNoClick(): void {
    this.dialogRef.close(false);
  }

  onRetry(): void {
    this.loadPdfDocument();
  }

  isControllerAndCanSign(): boolean {
    const isController = this.role.some(r => r.id === 3);
    const isStateZero = this.documentState === 0;

    // Además, verifica si el rol que DEBE firmar en este estado corresponde al Controlador
    const roleToSign = this.getRoleToSignByDocumentState().roleId;
    const isControllerTurn = roleToSign === 3;

    return isController && isStateZero && isControllerTurn;
  }

  onReturnToController(): void {
    const observation = this.userForm.get('observation')?.value;

    if (!observation || observation.trim() === '') {
      alert('Debe ingresar una observación para devolver el documento');
      return;
    }

    const formReturn = {
      documentId: this.documentId,
      observation: observation.trim()
    };

    this.documentSignatureService.returnDocumentToController(formReturn)
      .subscribe({
        next: (response) => {
          console.log('Documento devuelto exitosamente:', response);
          this.dialogRef.close({ action: 'returned', data: response });
        },
        error: (error) => {
          console.error('Error al devolver documento:', error);
          alert('Error al devolver el documento: ' + (error.message || 'Error desconocido'));
        }
    });
  }
}
