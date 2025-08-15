import { Component, Inject, inject, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogRef, MatDialogModule } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatIconModule } from '@angular/material/icon';
import { CommonModule } from '@angular/common'; // Importa CommonModule
import { ChangeDetectorRef } from '@angular/core';
import { DailyWorkLogService } from '../../../../../services/DailyWorkLogService/daily-work-log-service';

export interface UploadDialogData {
  isEdit: boolean;
  workLog: any;
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
    this.uploadForm = this.fb.group({
      end_time: [{value: '', disabled: true}, Validators.required],
      final_fuel: ['', [Validators.required, Validators.min(0)]],
      notes: [''] // Campo opcional para notas adicionales
    });
  }

  ngOnInit() {
    // Establecer la hora actual como hora de finalización por defecto
    this.setCurrentTime();
  }

  private setCurrentTime() {
    const now = new Date();
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const currentTime = `${hours}:${minutes}`;
    
    this.uploadForm.patchValue({
      end_time: currentTime
    });
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
      
      // Validar que se hayan seleccionado archivos
      if (this.selectedFiles.length === 0) {
        alert('Debes seleccionar al menos una imagen para completar el registro.');
        return;
      }

      this.isLoading = true;
      
      // Obtener valores incluyendo campos deshabilitados
      const formValue = this.uploadForm.getRawValue();
      
      // Crear FormData para envío multipart
      const formData = new FormData();
      formData.append('workLogId', this.data.workLog.id.toString());
      formData.append('end_time', formValue.end_time);
      formData.append('final_fuel', formValue.final_fuel.toString());
      
      if (formValue.notes) {
        formData.append('notes', formValue.notes);
      }

      // Agregar todas las imágenes
      this.selectedFiles.forEach((file, index) => {
        formData.append(`images[]`, file);
      });

      this.dailyWorkLogService.completeWorkLog(formData)
        .subscribe({
          next: (response) => {
            this.isLoading = false;
            this.cdr.detectChanges();
            this.dialogRef.close(response);
          },
          error: (error) => {
            this.isLoading = false;
            this.cdr.detectChanges();
            console.error('Error al completar el registro:', error);
            alert('Error al completar el registro. Por favor, intenta nuevamente.');
          }
        });
    }
  }

  // Getters para mostrar errores
  get endTimeError() {
    const control = this.uploadForm.get('end_time');
    if (control?.hasError('required') && control?.touched) {
      return 'La hora de finalización es requerida';
    }
    return '';
  }

  get finalFuelError() {
    const control = this.uploadForm.get('final_fuel');
    if (control?.hasError('required') && control?.touched) {
      return 'El combustible final es requerido';
    }
    if (control?.hasError('min') && control?.touched) {
      return 'El combustible final debe ser mayor o igual a 0';
    }
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