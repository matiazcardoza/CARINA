import { Component, Inject, inject, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogRef, MatDialogModule } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatNativeDateModule } from '@angular/material/core';
import { CommonModule } from '@angular/common';
import { ChangeDetectorRef } from '@angular/core';
import { DailyWorkLogService } from '../../../../../services/DailyWorkLogService/daily-work-log-service';

export interface DialogData {
  isEdit: boolean;
  workLog: any;
  workLogId?: string | number;
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
export class DailyWorkLogForm implements OnInit {
  
  workLogForm: FormGroup;
  isLoading = false;
  
  private fb = inject(FormBuilder);
  private dailyWorkLogService = inject(DailyWorkLogService);

  constructor(
    public dialogRef: MatDialogRef<DailyWorkLogForm>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData,
    private cdr: ChangeDetectorRef
  ) {
    this.workLogForm = this.fb.group({
      work_date: ['', Validators.required],
      start_time: ['', Validators.required],
      initial_fuel: ['', [Validators.required, Validators.min(0)]],
      description: ['']
    });
  }

  ngOnInit() {
    if (this.data.isEdit && this.data.workLog) {

      this.workLogForm.patchValue({
        work_date: this.data.workLog.work_date ? new Date(this.data.workLog.work_date) : null,
        start_time: this.data.workLog.start_time,
        initial_fuel: this.data.workLog.initial_fuel,
        description: this.data.workLog.description || ''
      });
    } else {
      const now = new Date();
      this.workLogForm.patchValue({
        work_date: now
      });
      this.setCurrentTime();
    }
  }

  private setCurrentTime() {
    const now = new Date();
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const currentTime = `${hours}:${minutes}`;
    
    this.workLogForm.patchValue({
      start_time: currentTime
    });
    this.cdr.detectChanges();
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

  onSubmit() {
    if (this.workLogForm.valid && !this.isLoading) {
      this.isLoading = true;
      
      const formValue = this.workLogForm.value;
      const workLogData = {
        work_date: this.formatDate(formValue.work_date),
        start_time: formValue.start_time,
        initial_fuel: parseFloat(formValue.initial_fuel),
        description: formValue.description,
        work_log_id: this.data.workLogId ? Number(this.data.workLogId) : null
      };

      if (this.data.isEdit && this.data.workLog?.id) {

        setTimeout(() => {
          this.dailyWorkLogService.updateWorkLog(this.data.workLog.id, workLogData)
            .subscribe({
              next: (updatedWorkLog) => {
                this.isLoading = false;
                this.cdr.detectChanges();
                setTimeout(() => {
                  this.dialogRef.close(updatedWorkLog);
                }, 100);
              },
              error: (error) => {
                this.isLoading = false;
                this.cdr.detectChanges();
                console.error('Error al actualizar:', error);
              }
          });
        }, 0);
      } else {

        setTimeout(() => {
          this.dailyWorkLogService.createWorkLog(workLogData)
            .subscribe({
              next: (newWorkLog) => {
                this.isLoading = false;
                this.cdr.detectChanges();
                setTimeout(() => {
                  this.dialogRef.close(newWorkLog);
                }, 100);
              },
              error: (error) => {
                this.isLoading = false;
                this.cdr.detectChanges();
                console.error('Error al crear:', error);
              }
            });
        }, 0);
      }
    }
  }

  private formatDate(date: Date): string {

    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  private compareTime(time1: string, time2: string): number {
    const [h1, m1] = time1.split(':').map(Number);
    const [h2, m2] = time2.split(':').map(Number);
    
    const minutes1 = h1 * 60 + m1;
    const minutes2 = h2 * 60 + m2;
    
    return minutes1 - minutes2;
  }


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
    return '';
  }

  get initialFuelError() {
    const control = this.workLogForm.get('initial_fuel');
    if (control?.hasError('required') && control?.touched) {
      return 'El combustible inicial es requerido';
    }
    if (control?.hasError('min') && control?.touched) {
      return 'El combustible inicial debe ser mayor o igual a 0';
    }
    return '';
  }

  get descriptionError() {
    const control = this.workLogForm.get('description');
    return '';
  }
}