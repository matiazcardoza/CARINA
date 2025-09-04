import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { Inject } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { MatIconModule } from '@angular/material/icon';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatDividerModule } from '@angular/material/divider';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { Observable, catchError, of } from 'rxjs';
import { MechanicalEquipmentService } from '../../../../../services/MechanicalEquipmentService/mechanical-equipment-service';
import { DailyWorkLogService } from '../../../../../services/DailyWorkLogService/daily-work-log-service';

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
  

  constructor(
    public dialogRef: MatDialogRef<MechanicalEquipmentWork>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData,
    private fb: FormBuilder,
    private cdr: ChangeDetectorRef,
    private mechanicalEquipmentService: MechanicalEquipmentService,
    private dailyWorkLogService: DailyWorkLogService
  ) {
    // FormGroup para la búsqueda de Meta
    this.metaSearchForm = this.fb.group({
      metaCode: ['', [Validators.required, Validators.minLength(3)]]
    });
    this.operatorForm = this.fb.group({
      operatorName: ['', Validators.required]
    });
  }

  ngOnInit(): void {
    // Inicialización del componente
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

  // Función para procesar la meta seleccionada (ejemplo)
  procesarMeta(): void {
    const formData = new FormData();
        
    formData.append('maquinaria_id', this.data.mechanicalEquipment.id.toString());
    formData.append('maquinaria_equipo', this.data.mechanicalEquipment.machinery_equipment || '');
    formData.append('maquinaria_marca', this.data.mechanicalEquipment.brand || '');
    formData.append('maquinaria_modelo', this.data.mechanicalEquipment.model || '');
    formData.append('maquinaria_serie', this.data.mechanicalEquipment.serial_number || '');
    formData.append('operador', this.operatorForm.value.operatorName || '');
        
    if (this.selectedMeta) {
      formData.append('meta_id', this.selectedMeta.idmeta || '');
      formData.append('meta_codigo', this.selectedMeta.codmeta || '');
      formData.append('meta_descripcion', this.selectedMeta.desmeta || '');
    }

    this.dailyWorkLogService.importOrder(formData).subscribe({
      next: (response) => {
        this.isLoading = false;
        this.cdr.detectChanges();
        console.log(response.message);
        this.dialogRef.close(response);
      },
      error: (error) => {
        console.error('Error al importar:', error);
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  // Getter para verificar si se puede procesar
  get canProcess(): boolean {
    return !!this.selectedMeta;
  }
}