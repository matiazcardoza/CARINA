import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { Inject } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule, AbstractControl, ValidationErrors, FormArray } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { MatIconModule } from '@angular/material/icon';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatDividerModule } from '@angular/material/divider';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { Observable, catchError, of } from 'rxjs';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatNativeDateModule } from '@angular/material/core';
import { MechanicalEquipmentService } from '../../../../../services/MechanicalEquipmentService/mechanical-equipment-service';
import { DailyWorkLogService } from '../../../../../services/DailyWorkLogService/daily-work-log-service';
import { WorkLogElement } from '../../../daily-work-log/daily-work-log';

interface MetaData {
  idmeta: string;
  codmeta: string;
  desmeta: string;
}

interface MetaApiResponse {
  current_page: number;
  data: any[];
  first_page_url: string;
  from: number;
  last_page: number;
  total: number;
}

export interface DialogData {
  mechanicalEquipment: any;
  isReassigned?: boolean;
}

export function dateRangeValidator(isReassigned: boolean) {
  return (control: AbstractControl): ValidationErrors | null => {
    if (!isReassigned) {
      return null;
    }
    const startDate = control.get('start_date')?.value;
    const endDate = control.get('end_date')?.value;
    if (startDate && endDate) {
      const start = new Date(startDate);
      const end = new Date(endDate);
      start.setHours(0, 0, 0, 0);
      end.setHours(0, 0, 0, 0);
      if (end <= start) {
        return { dateRangeInvalid: true };
      }
    }
    return null;
  };
}

@Component({
  selector: 'app-mechanical-equipment-work',
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatIconModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatCardModule,
    MatDividerModule,
    MatProgressSpinnerModule,
    MatDatepickerModule,
    MatNativeDateModule
  ],
  templateUrl: './mechanical-equipment-work.html',
  styleUrl: './mechanical-equipment-work.css'
})
export class MechanicalEquipmentWork implements OnInit {
  metaSearchForm: FormGroup;
  operatorForm: FormGroup;
  operators: FormArray;
  selectedMeta: MetaData | null = null;
  isLoadingMeta = false;
  metaErrorMessage = '';
  isLoading = false;
  dataMeta: WorkLogElement[] = [];
  
  // ❌ ELIMINADO: Fechas mínimas y máximas
  // minDate = new Date(); // Fecha actual como mínimo
  // maxDate = new Date(2030, 11, 31); // Fecha máxima

  constructor(
    public dialogRef: MatDialogRef<MechanicalEquipmentWork>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData,
    private fb: FormBuilder,
    private cdr: ChangeDetectorRef,
    private mechanicalEquipmentService: MechanicalEquipmentService,
    private dailyWorkLogService: DailyWorkLogService
  ) {
    this.metaSearchForm = this.fb.group({
      metaCode: ['', [Validators.required, Validators.minLength(3)]]
    });

    this.operatorForm = this.fb.group({
      start_date: [''],
      end_date: [''],
      operators: this.fb.array([])
    }, { validators: dateRangeValidator(this.data.isReassigned || false) });
    this.operators = this.operatorForm.get('operators') as FormArray;
  }

  ngOnInit(): void {
    // ❌ ELIMINADO: Configuración de fecha mínima
    // this.minDate = new Date();

    this.updateDateValidators();
    
    if (this.operators.length === 0) {
      this.addOperator();
    }

    if (this.data.isReassigned) {
      console.log('es reasignado');
      this.loadExistingData();
    }
  }

  private updateDateValidators(): void {
    const startDateControl = this.operatorForm.get('start_date');
    const endDateControl = this.operatorForm.get('end_date');
    
    if (this.data.isReassigned) {
      startDateControl?.clearValidators();
      endDateControl?.clearValidators();
    } else {
      startDateControl?.setValidators([Validators.required]);
      endDateControl?.setValidators([Validators.required]);
    }
    startDateControl?.updateValueAndValidity();
    endDateControl?.updateValueAndValidity();
  }

  private createOperatorGroup(operator?: { id?: number, name?: string }): FormGroup {
    return this.fb.group({
      operatorId: [operator?.id || null],
      operatorName: [operator?.name || '', Validators.required]
    });
  }

  addOperator(): void {
    this.operators.push(this.createOperatorGroup());
    this.cdr.detectChanges();
  }

  removeOperator(index: number): void {
    if (this.operators.length > 1) {
      this.operators.removeAt(index);
      this.cdr.detectChanges();
    }
  }

  get operatorsControls() {
    return (this.operatorForm.get('operators') as FormArray).controls;
  }

  private loadExistingData() {
    console.log('loadExistingData');
    this.isLoading = true;
    
    if (this.data.mechanicalEquipment.operators && 
        Array.isArray(this.data.mechanicalEquipment.operators) && 
        this.data.mechanicalEquipment.operators.length > 0) {
      
      this.preloadFromMechanicalEquipment(this.data.mechanicalEquipment);
      this.isLoading = false;
      return;
    }
  }

  private preloadFromMechanicalEquipment(equipment: any): void {
    console.log('preloadFromMechanicalEquipment', equipment);
    this.operators.clear();
    
    this.operatorForm.patchValue({
      start_date: equipment.start_date ? this.parseDateAsLocal(equipment.start_date) : null,
      end_date: equipment.end_date ? this.parseDateAsLocal(equipment.end_date) : null
    });
    
    if (equipment.operators && Array.isArray(equipment.operators) && equipment.operators.length > 0) {
      equipment.operators.forEach((op: any) => {
        this.operators.push(this.createOperatorGroup({
          id: op.id,
          name: op.name
        }));
      });
    } else {
      this.addOperator();
    }
    
    if (equipment.goal_detail) {
      this.selectedMeta = {
        idmeta: equipment.goal_id.toString(),
        codmeta: equipment.goal_project,
        desmeta: equipment.goal_detail
      };

      this.metaSearchForm.patchValue({
        metaCode: equipment.goal_project || ''
      });

      this.dataMeta = [equipment];
    }
    
    this.cdr.detectChanges();
  }

  private parseDateAsLocal(dateString: string): Date {
    const [year, month, day] = dateString.split('-').map(Number);
    return new Date(year, month - 1, day);
  }

  buscarMeta(): void {
    if (this.metaSearchForm.valid) {
      const metaCode = this.metaSearchForm.get('metaCode')?.value?.trim();
      
      if (!metaCode) {
        this.metaErrorMessage = 'Por favor ingrese un código de meta válido';
        return;
      }

      this.isLoadingMeta = true;
      this.metaErrorMessage = '';
      this.selectedMeta = null;

      this.mechanicalEquipmentService.getMetaByCode(metaCode)
        .pipe(
          catchError(error => {
            console.error('Error al buscar Meta:', error);
            this.metaErrorMessage = `No se encontró información para el código: ${metaCode}`;
            return of(null);
          })
        )
        .subscribe((response: MetaApiResponse | null) => {
          this.isLoadingMeta = false;
          
          if (response && response.data && response.data.length > 0) {
            // Extraer solo los datos que necesitas del primer elemento
            const metaItem = response.data[0];
            this.selectedMeta = {
              idmeta: metaItem.idmeta,
              codmeta: metaItem.codmeta,
              desmeta: metaItem.desmeta
            };
          } else {
            this.metaErrorMessage = `No se encontraron datos para el código: ${metaCode}`;
          }
          
          this.cdr.detectChanges();
        });
    } else {
      this.metaErrorMessage = 'Por favor ingrese un código válido (mínimo 3 caracteres)';
    }
  }

  limpiarBusquedaMeta(): void {
    this.metaSearchForm.reset();
    this.selectedMeta = null;
    this.metaErrorMessage = '';
  }

  procesarMeta(): void {
    if (!this.operatorForm.valid) {
      this.operatorForm.markAllAsTouched();
      return;
    }

    this.isLoading = true;

    const startDateValue = this.operatorForm.get('start_date')?.value;
    const endDateValue = this.operatorForm.get('end_date')?.value;
    
    const startDate = startDateValue ? this.formatDate(new Date(startDateValue)) : '';
    const endDate = endDateValue ? this.formatDate(new Date(endDateValue)) : '';
    
    const operatorData = this.operators.controls.map((control) => {
      const operatorGroup = control as FormGroup;
      return {
        id: operatorGroup.value.operatorId || null,
        name: operatorGroup.value.operatorName || ''
      };
    });

    if (this.data.isReassigned && this.dataMeta.length > 0) {
      const workLogData = {
        ...this.dataMeta[0],
        start_date: startDate,
        end_date: endDate,
        operators: operatorData,
        service_id: this.data.mechanicalEquipment.service_id,
        goal_id: this.selectedMeta ? parseInt(this.selectedMeta.idmeta) : this.dataMeta[0].goal_id,
        goal_project: this.selectedMeta?.codmeta || this.dataMeta[0].goal_project,
        goal_detail: this.selectedMeta?.desmeta || this.dataMeta[0].goal_detail
      };

      this.dailyWorkLogService.updateIdmeta(workLogData).subscribe({
        next: (response) => {
          this.isLoading = false;
          this.cdr.detectChanges();
          console.log('Actualización exitosa:', response);
          this.dialogRef.close(response);
        },
        error: (error) => {
          console.error('Error al actualizar:', error);
          this.isLoading = false;
          this.cdr.detectChanges();
        }
      });
      
    } else {
      const formData = new FormData();
      formData.append('maquinaria_id', this.data.mechanicalEquipment.id.toString());
      formData.append('maquinaria_equipo', this.data.mechanicalEquipment.machinery_equipment || '');
      formData.append('maquinaria_marca', this.data.mechanicalEquipment.brand || '');
      formData.append('maquinaria_modelo', this.data.mechanicalEquipment.model || '');
      formData.append('maquinaria_placa', this.data.mechanicalEquipment.plate || '');
      formData.append('maquinaria_serie', this.data.mechanicalEquipment.serial_number || '');
      
      formData.append('start_date', startDate);
      formData.append('end_date', endDate);
      
      formData.append('operators', JSON.stringify(operatorData));
      
      if (this.selectedMeta) {
        formData.append('meta_id', this.selectedMeta.idmeta || '');
        formData.append('meta_codigo', this.selectedMeta.codmeta || '');
        formData.append('meta_descripcion', this.selectedMeta.desmeta || '');
      }

      this.dailyWorkLogService.importOrder(formData).subscribe({
        next: (response) => {
          this.isLoading = false;
          this.cdr.detectChanges();
          console.log('Creación exitosa:', response);
          this.dialogRef.close(response);
        },
        error: (error) => {
          console.error('Error al importar:', error);
          this.isLoading = false;
          this.cdr.detectChanges();
        }
      });
    }
  }

  private formatDate(date: Date): string {
    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  get canProcess(): boolean {
    return !!this.selectedMeta && this.operatorForm.valid && this.operators.length > 0;
  }

  get startDateError(): string {
    if (!this.data.isReassigned) return '';
    const control = this.operatorForm.get('start_date');
    if (control?.hasError('required')) return 'La fecha inicial es requerida';
    if (control?.hasError('matDatepickerParse')) return 'Formato de fecha inválido';
    return '';
  }

  get endDateError(): string {
    if (!this.data.isReassigned) return '';
    const control = this.operatorForm.get('end_date');
    if (control?.hasError('required')) return 'La fecha final es requerida';
    if (control?.hasError('matDatepickerParse')) return 'Formato de fecha inválido';
    if (this.operatorForm.hasError('dateRangeInvalid') && control?.value) {
      return 'La fecha final debe ser posterior a la fecha inicial';
    }
    return '';
  }
}