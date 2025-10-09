import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { Inject } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule, AbstractControl, ValidationErrors } from '@angular/forms';
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

// Interfaz para los datos de Meta que necesitas
interface MetaData {
  idmeta: string;
  codmeta: string;
  desmeta: string;
}

// Interfaz para la respuesta completa de la API
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

// Validador personalizado para fechas
export function dateRangeValidator(control: AbstractControl): ValidationErrors | null {
  const startDate = control.get('start_date')?.value;
  const endDate = control.get('end_date')?.value;
  
  if (startDate && endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    
    // Resetear las horas para comparar solo las fechas
    start.setHours(0, 0, 0, 0);
    end.setHours(0, 0, 0, 0);
    
    if (end <= start) {
      return { dateRangeInvalid: true };
    }
  }
  
  return null;
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
      operatorName: ['', Validators.required],
      start_date: [''],
      end_date: ['']
    }, { validators: dateRangeValidator });
  }

  ngOnInit(): void {
    // ❌ ELIMINADO: Configuración de fecha mínima
    // this.minDate = new Date();
    
    // Escuchar cambios en las fechas para revalidar
    this.operatorForm.get('start_date')?.valueChanges.subscribe(() => {
      this.operatorForm.get('end_date')?.updateValueAndValidity();
    });

    if (this.data.isReassigned) {
      this.loadExistingData();
    }
  }

  private loadExistingData() {
    this.isLoading = true;
    this.dailyWorkLogService.getIdmeta(this.data.mechanicalEquipment.id).subscribe({
      next: (data) => {
        console.log('Productos cargados:', data);
        this.dataMeta = data;
        if (data && data.length > 0) {
          this.preloadFormData(data[0]);
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (error) => {
        console.error('Error al cargar meta:', error);
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  private parseDateAsLocal(dateString: string): Date {
    const [year, month, day] = dateString.split('-').map(Number);
    return new Date(year, month - 1, day);
  }

  private preloadFormData(workLog: WorkLogElement): void {
    // Prellenar formulario del operador
    this.operatorForm.patchValue({
      operatorName: workLog.operator || '',
      start_date: workLog.start_date ? this.parseDateAsLocal(workLog.start_date) : null,
      end_date: workLog.end_date ? this.parseDateAsLocal(workLog.end_date) : null
    });
    // Prellenar información de Meta si existe
    if (workLog.goal_id) {
      this.selectedMeta = {
        idmeta: workLog.goal_id.toString(),
        codmeta: workLog.goal_project || '',
        desmeta: workLog.goal_detail || ''
      };
      
      // Prellenar el código de meta en el formulario de búsqueda
      this.metaSearchForm.patchValue({
        metaCode: workLog.goal_project || ''
      });
    }

    this.cdr.detectChanges();
  }


  // Función para buscar Meta
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

      // Llamada al servicio de Meta
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

  // Función para limpiar la búsqueda de Meta
  limpiarBusquedaMeta(): void {
    this.metaSearchForm.reset();
    this.selectedMeta = null;
    this.metaErrorMessage = '';
  }

  // Función para procesar la meta seleccionada
  procesarMeta(): void {
    // Validar que el formulario del operador sea válido
    if (!this.operatorForm.valid) {
      this.operatorForm.markAllAsTouched();
      return;
    }

    this.isLoading = true;

    // ✅ MODO EDICIÓN: Si es reasignación y hay datos existentes
    if (this.data.isReassigned && this.dataMeta.length > 0) {
      const workLogData: WorkLogElement = {
        ...this.dataMeta[0], // Mantener datos originales
        operator: this.operatorForm.value.operatorName || '',
        start_date: this.formatDate(new Date(this.operatorForm.value.start_date)),
        end_date: this.formatDate(new Date(this.operatorForm.value.end_date)),
        // Actualizar meta si existe
        goal_id: this.selectedMeta ? parseInt(this.selectedMeta.idmeta) : this.dataMeta[0].goal_id,
        goal_project: this.selectedMeta?.codmeta || this.dataMeta[0].goal_project,
        goal_detail: this.selectedMeta?.desmeta || this.dataMeta[0].goal_detail
      };

      // Llamar al servicio de actualización
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
      // ✅ MODO CREACIÓN: Crear nuevo registro con FormData
      const formData = new FormData();
      
      formData.append('maquinaria_id', this.data.mechanicalEquipment.id.toString());
      formData.append('maquinaria_equipo', this.data.mechanicalEquipment.machinery_equipment || '');
      formData.append('maquinaria_marca', this.data.mechanicalEquipment.brand || '');
      formData.append('maquinaria_modelo', this.data.mechanicalEquipment.model || '');
      formData.append('maquinaria_placa', this.data.mechanicalEquipment.plate || '');
      formData.append('maquinaria_serie', this.data.mechanicalEquipment.serial_number || '');
      formData.append('operador', this.operatorForm.value.operatorName || '');
      
      const startDate = new Date(this.operatorForm.value.start_date);
      formData.append('start_date', this.formatDate(startDate));
      
      const endDate = new Date(this.operatorForm.value.end_date);
      formData.append('end_date', this.formatDate(endDate));
      
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

  // Función para formatear fecha a string
  private formatDate(date: Date): string {
    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  // Getter para verificar si se puede procesar
  get canProcess(): boolean {
    return !!this.selectedMeta && this.operatorForm.valid;
  }

  // Getter para errores de fecha inicial
  get startDateError(): string {
    const control = this.operatorForm.get('start_date');
    if (control?.hasError('required')) return 'La fecha inicial es requerida';
    if (control?.hasError('matDatepickerParse')) return 'Formato de fecha inválido';
    return '';
  }

  // Getter para errores de fecha final
  get endDateError(): string {
    const control = this.operatorForm.get('end_date');
    
    if (control?.hasError('required')) return 'La fecha final es requerida';
    if (control?.hasError('matDatepickerParse')) return 'Formato de fecha inválido';
    
    // Error de rango de fechas a nivel de formulario
    if (this.operatorForm.hasError('dateRangeInvalid') && control?.value) {
      return 'La fecha final debe ser posterior a la fecha inicial';
    }
    
    return '';
  }
}