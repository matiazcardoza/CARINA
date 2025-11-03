import { Component, OnInit, ChangeDetectorRef, Inject } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { MatIconModule } from '@angular/material/icon';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { catchError, of } from 'rxjs';
import { MechanicalEquipmentService } from '../../../../../services/MechanicalEquipmentService/mechanical-equipment-service';
import { DailyWorkLogService } from '../../../../../services/DailyWorkLogService/daily-work-log-service';

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
}

@Component({
  selector: 'app-mechanical-equipment-support',
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatIconModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatCardModule,
    MatProgressSpinnerModule
  ],
  templateUrl: './mechanical-equipment-support.html',
  styleUrl: './mechanical-equipment-support.css'
})
export class MechanicalEquipmentSupport implements OnInit {
  metaSearchForm: FormGroup;
  selectedMeta: MetaData | null = null;                                                                     
  isLoadingMeta = false;
  metaErrorMessage = '';
  isLoading = false;
  dataMeta: any[] = [];

  constructor(
    public dialogRef: MatDialogRef<MechanicalEquipmentSupport>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData,
    private fb: FormBuilder,
    private cdr: ChangeDetectorRef,
    private mechanicalEquipmentService: MechanicalEquipmentService,
    private dailyWorkLogService: DailyWorkLogService
  ) {
    this.metaSearchForm = this.fb.group({
      metaCode: ['', [Validators.required, Validators.minLength(3)]]
    });
  }

  ngOnInit(): void {
    this.loadExistingData();
  }

  private loadExistingData(): void {
    this.isLoading = true;
    
    if (this.data.mechanicalEquipment.goal_project) {
      this.selectedMeta = {
        idmeta: this.data.mechanicalEquipment.goal_id?.toString() || '',
        codmeta: this.data.mechanicalEquipment.goal_project || '',
        desmeta: this.data.mechanicalEquipment.goal_detail || ''
      };

      this.metaSearchForm.patchValue({
        metaCode: this.data.mechanicalEquipment.goal_project || ''
      });
    }
    
    this.isLoading = false;
    this.cdr.detectChanges();
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

  asignarApoyo(): void {
    if (!this.selectedMeta) {
      return;
    }

    this.isLoading = true;

    const formData = new FormData();
    formData.append('id', this.data.mechanicalEquipment.id.toString());
    formData.append('machinery_equipment', this.data.mechanicalEquipment.machinery_equipment || '');
    formData.append('brand', this.data.mechanicalEquipment.brand || '');
    formData.append('model', this.data.mechanicalEquipment.model || '');
    formData.append('plate', this.data.mechanicalEquipment.plate || '');
    formData.append('serial_number', this.data.mechanicalEquipment.serial_number || '');
    formData.append('service_id', this.data.mechanicalEquipment.service_id || '');
    formData.append('goal_id', this.selectedMeta.idmeta);
    formData.append('goal_project', this.selectedMeta.codmeta);
    formData.append('goal_detail', this.selectedMeta.desmeta);
    formData.append('tipo', 'apoyo'); // Identificador para indicar que es apoyo

    // Aquí puedes usar el método del servicio que corresponda para guardar el apoyo
    this.mechanicalEquipmentService.supportMachinery(formData).subscribe({
      next: (response) => {
        this.isLoading = false;
        this.cdr.detectChanges();
        console.log('Asignación de apoyo exitosa:', response);
        this.dialogRef.close(response);
      },
      error: (error) => {
        console.error('Error al asignar apoyo:', error);
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  get canProcess(): boolean {
    return !!this.selectedMeta && !this.isLoading;
  }
}