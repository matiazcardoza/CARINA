import { Component, Inject, inject, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogRef, MatDialogModule } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatNativeDateModule } from '@angular/material/core';
import { CommonModule } from '@angular/common';
import { DailyWorkLogService } from '../../../../../services/DailyWorkLogService/daily-work-log-service';

export interface DialogData {
  isEdit: boolean;
  workLog: any;
}

@Component({
  selector: 'app-daily-parts-form',
  templateUrl: './daily-work-log-form.html',
  styleUrls: ['./daily-work-log-form.css'],
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatDialogModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
    MatDatepickerModule,
    MatNativeDateModule
  ]
})
export class DailyPartsFormComponent implements OnInit {
  workLogForm: FormGroup;
  isLoading = false;
  
  private fb = inject(FormBuilder);
  private dailyWorkLogService = inject(DailyWorkLogService);

  constructor(
    public dialogRef: MatDialogRef<DailyPartsFormComponent>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData
  ) {
    this.workLogForm = this.fb.group({
      work_date: ['', Validators.required],
      start_time: ['', [Validators.required, Validators.pattern(/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/)]],
      final_time: ['', [Validators.required, Validators.pattern(/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/)]]
    });
  }

  ngOnInit() {
    if (this.data.isEdit && this.data.workLog) {
      // Si es edición, llenar el formulario con los datos existentes
      this.workLogForm.patchValue({
        work_date: new Date(this.data.workLog.work_date),
        start_time: this.data.workLog.start_time,
        final_time: this.data.workLog.final_time
      });
    }
  }

  get title(): string {
    return this.data.isEdit ? 'Editar Registro de Trabajo' : 'Nuevo Registro de Trabajo';
  }

  get submitButtonText(): string {
    return this.data.isEdit ? 'Actualizar' : 'Crear';
  }



  onCancel() {
    this.dialogRef.close(false);
  }

  private formatDate(date: Date): string {
    // Formatear fecha como YYYY-MM-DD
    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  private compareTime(time1: string, time2: string): number {
    // Comparar dos horas en formato HH:MM
    // Retorna < 0 si time1 < time2, 0 si son iguales, > 0 si time1 > time2
    const [h1, m1] = time1.split(':').map(Number);
    const [h2, m2] = time2.split(':').map(Number);
    
    const minutes1 = h1 * 60 + m1;
    const minutes2 = h2 * 60 + m2;
    
    return minutes1 - minutes2;
  }

  // Getters para mostrar errores
  get workDateError() {
    const control = this.workLogForm.get('work_date');
    if (control?.hasError('required') && control?.touched) {
      return 'La fecha de trabajo es requerida';
    }
    return '';
  }

  get startTimeError() {
    const control = this.workLogForm.get('start_time');
    if (control?.hasError('required') && control?.touched) {
      return 'La hora inicial es requerida';
    }
    if (control?.hasError('pattern') && control?.touched) {
      return 'Formato de hora inválido (HH:MM)';
    }
    return '';
  }

  get finalTimeError() {
    const control = this.workLogForm.get('final_time');
    if (control?.hasError('required') && control?.touched) {
      return 'La hora final es requerida';
    }
    if (control?.hasError('pattern') && control?.touched) {
      return 'Formato de hora inválido (HH:MM)';
    }
    return '';
  }
}