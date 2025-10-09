import { Component, Inject, inject, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogRef, MatDialogModule } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatIconModule } from '@angular/material/icon';
import { CommonModule } from '@angular/common';
import { ChangeDetectorRef } from '@angular/core';
import { DailyWorkLogService } from '../../../../../services/DailyWorkLogService/daily-work-log-service';

export interface UploadDialogData {
  isEdit: boolean;
  workLog: any;
  serviceId: string | number;
}

@Component({
  selector: 'app-daily-work-log-upload',
  templateUrl: './daily-work-log-upload.html',
  styleUrls: ['./daily-work-log-upload.css'],
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatDialogModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
    MatIconModule
  ]
})
export class DailyWorkLogUpload implements OnInit {

  uploadForm: FormGroup;
  isLoading = false;
  selectedFiles: File[] = [];
  previewUrls: string[] = [];
  maxFiles = 5; // Máximo número de archivos
  maxFileSize = 5 * 1024 * 1024; // 5MB por archivo
  allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

  private fb = inject(FormBuilder);
  private dailyWorkLogService = inject(DailyWorkLogService);

  constructor(
    public dialogRef: MatDialogRef<DailyWorkLogUpload>,
    @Inject(MAT_DIALOG_DATA) public data: UploadDialogData,
    private cdr: ChangeDetectorRef
  ) {
    // CAMBIO PRINCIPAL: Inicializar el formulario igual que en el componente principal
    this.uploadForm = this.fb.group({
      end_time: ['', Validators.required], // Sin disabled: true aquí
      occurrence: [''] // Campo opcional para notas adicionales
    });
  }

  ngOnInit() {
    // APLICAR LA MISMA LÓGICA: Establecer la hora actual y luego deshabilitar
    this.setCurrentTime();

    // Deshabilitar el campo después de establecer el valor, igual que en el componente principal
    //this.uploadForm.get('end_time')?.disable();
  }

  private setCurrentTime() {
    const now = new Date();
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const currentTime = `${hours}:${minutes}`;

    this.uploadForm.patchValue({
      end_time: currentTime
    });
    this.cdr.detectChanges();
  }

  onFileSelect(event: any) {
    const files = Array.from(event.target.files) as File[];

    // Validar número máximo de archivos
    if (this.selectedFiles.length + files.length > this.maxFiles) {
      alert(`Solo puedes subir un máximo de ${this.maxFiles} imágenes`);
      return;
    }

    // Validar cada archivo
    for (const file of files) {
      if (!this.validateFile(file)) {
        continue;
      }

      this.selectedFiles.push(file);

      // Crear preview de la imagen
      const reader = new FileReader();
      reader.onload = (e: any) => {
        this.previewUrls.push(e.target.result);
        this.cdr.detectChanges();
      };
      reader.readAsDataURL(file);
    }

    // Limpiar el input
    event.target.value = '';
  }

  private validateFile(file: File): boolean {
    // Validar tipo de archivo
    if (!this.allowedTypes.includes(file.type)) {
      alert(`Tipo de archivo no permitido: ${file.name}. Solo se permiten imágenes JPG, PNG y WebP.`);
      return false;
    }

    // Validar tamaño
    if (file.size > this.maxFileSize) {
      alert(`El archivo ${file.name} es muy grande. El tamaño máximo es 5MB.`);
      return false;
    }

    return true;
  }

  removeFile(index: number) {
    this.selectedFiles.splice(index, 1);
    this.previewUrls.splice(index, 1);
    this.cdr.detectChanges();
  }

  onCancel() {
    this.dialogRef.close(false);
  }

  onSubmit() {
    if (this.uploadForm.valid && !this.isLoading) {
      /*if (this.selectedFiles.length === 0) {
        alert('Debes seleccionar al menos una imagen para completar el registro.');
        return;
      }*/

      this.isLoading = true;

      const formValue = this.uploadForm.getRawValue();
      const formData = new FormData();

      formData.append('workLogId', this.data.workLog.id.toString());
      formData.append('end_time', formValue.end_time);
      formData.append('occurrence', formValue.occurrence || '');
      formData.append('serviceId', this.data.serviceId.toString());

      this.selectedFiles.forEach((file, index) => {
        formData.append(`images[]`, file);
      });

      setTimeout(() => {
        this.dailyWorkLogService.completeWorkLog(formData)
          .subscribe({
            next: (response) => {
              this.isLoading = false;
              this.cdr.detectChanges();
              setTimeout(() => {
                this.dialogRef.close(response || true);
              }, 100);
            },
            error: (error) => {
              this.isLoading = false;
              this.cdr.detectChanges();
              console.error('Error al completar el registro:', error);
              alert('Error al completar el registro. Por favor, intenta nuevamente.');
            }
          });
      }, 0);
    }
  }

  // AGREGAR: Getters para errores igual que en el componente principal
  get endTimeError() {
    const control = this.uploadForm.get('end_time');
    if (control?.hasError('required') && control?.touched) {
      return 'La hora de finalización es requerida';
    }
    return '';
  }

  get occurrenceError() {
    const control = this.uploadForm.get('occurrence');
    return '';
  }

  // Formatear el tamaño del archivo para mostrar
  formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }
}
