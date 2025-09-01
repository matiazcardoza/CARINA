import { Component, OnInit, ChangeDetectorRef, Inject } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { MatIconModule } from '@angular/material/icon';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatDividerModule } from '@angular/material/divider';
import { MatChipsModule } from '@angular/material/chips';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { DailyWorkLogService } from '../../../../../services/DailyWorkLogService/daily-work-log-service';
import { TextFieldModule } from '@angular/cdk/text-field';
import { MatSelectModule } from '@angular/material/select';
import { MatOptionModule } from '@angular/material/core';
import { MatAutocompleteModule } from '@angular/material/autocomplete';
import { Observable, startWith, map, catchError, of } from 'rxjs';
import { MechanicalEquipmentService } from '../../../../../services/MechanicalEquipmentService/mechanical-equipment-service';
import { MechanicalEquipmentElement } from '../../../../private/mechanical-equipment/mechanical-equipment';

interface EstadoMaquinaria {
  value: number;
  label: string;
  color: 'primary' | 'accent' | 'warn';
}

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

@Component({
  selector: 'app-daily-work-log-mechanical',
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatIconModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatCardModule,
    MatDividerModule,
    MatChipsModule,
    MatProgressSpinnerModule,
    TextFieldModule,
    MatSelectModule,
    MatOptionModule,
    MatAutocompleteModule,
  ],
  templateUrl: './daily-work-log-mechanical.html',
  styleUrl: './daily-work-log-mechanical.css'
})
export class DailyWorkLogMechanical implements OnInit {
  searchForm: FormGroup;
  metaSearchForm: FormGroup; // Nuevo FormGroup para Meta
  filteredMaquinaria: Observable<MechanicalEquipmentElement[]>;
  selectedMaquinaria: MechanicalEquipmentElement | null = null;
  selectedMeta: MetaData | null = null; // Nueva propiedad para Meta seleccionada
  maquinariaList: MechanicalEquipmentElement[] = [];
  isLoading = false;
  isLoadingMeta = false; // Loading específico para Meta
  errorMessage = '';
  metaErrorMessage = ''; // Error específico para Meta

  // Estados de maquinaria con colores correspondientes
  estadosMap: EstadoMaquinaria[] = [
    { value: 1, label: 'Operativo', color: 'primary' },
    { value: 2, label: 'En Mantenimiento', color: 'accent' },
    { value: 3, label: 'Averiado', color: 'warn' },
    { value: 4, label: 'Fuera de Servicio', color: 'warn' }
  ];

  constructor(
    private fb: FormBuilder,
    private cdr: ChangeDetectorRef,
    public dialogRef: MatDialogRef<DailyWorkLogMechanical>,
    @Inject(MAT_DIALOG_DATA) public data: any,
    private dailyWorkLogService: DailyWorkLogService,
    private mechanicalEquipmentService: MechanicalEquipmentService
  ) {
    this.searchForm = this.fb.group({
      maquinariaSearch: ['', Validators.required],
      operador: ['', Validators.required]
    });

    // Nuevo FormGroup para la búsqueda de Meta
    this.metaSearchForm = this.fb.group({
      metaCode: ['', [Validators.required, Validators.minLength(3)]]
    });

    this.filteredMaquinaria = this.searchForm.get('maquinariaSearch')!.valueChanges.pipe(
      startWith(''),
      map(value => this._filterMaquinaria(typeof value === 'string' ? value : value?.machinery_equipment || ''))
    );
  }

  ngOnInit(): void {
    this.loadMechanicalEquipment();
  }

  loadMechanicalEquipment(): void {
    this.isLoading = true;
    this.errorMessage = '';
    
    this.mechanicalEquipmentService.getMechanicalEquipment()
      .pipe(
        catchError(error => {
          console.error('Error loading mechanical equipment:', error);
          this.errorMessage = 'Error al cargar la maquinaria. Por favor, intente nuevamente.';
          return of([]);
        })
      )
      .subscribe(equipment => {
        this.maquinariaList = equipment;
        this.isLoading = false;
        this.cdr.detectChanges();
      });
  }

  private _filterMaquinaria(value: string): MechanicalEquipmentElement[] {
    if (!value) {
      return this.maquinariaList;
    }

    const filterValue = value.toLowerCase();
    return this.maquinariaList.filter(maquinaria => 
      maquinaria.machinery_equipment?.toLowerCase().includes(filterValue) ||
      maquinaria.brand?.toLowerCase().includes(filterValue) ||
      maquinaria.model?.toLowerCase().includes(filterValue) ||
      maquinaria.plate?.toLowerCase().includes(filterValue) ||
      maquinaria.serial_number?.toLowerCase().includes(filterValue)
    );
  }

  displayMaquinaria(maquinaria: MechanicalEquipmentElement): string {
    return maquinaria ? `${maquinaria.plate || 'N/A'} - ${maquinaria.machinery_equipment}` : '';
  }

  onMaquinariaSelected(maquinaria: MechanicalEquipmentElement): void {
    this.selectedMaquinaria = maquinaria;
  }

  buscarMaquinaria(): void {
    const searchValue = this.searchForm.get('maquinariaSearch')?.value;
    if (typeof searchValue === 'string') {
      // Si es texto, buscar por coincidencia
      const results = this._filterMaquinaria(searchValue);
      console.log('Resultados de búsqueda:', results);
    } else if (searchValue && typeof searchValue === 'object') {
      // Si es un objeto (maquinaria seleccionada)
      this.onMaquinariaSelected(searchValue);
    }
  }

  // Nueva función para buscar Meta
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

  nuevaBusqueda(): void {
    this.searchForm.reset();
    this.selectedMaquinaria = null;
    this.limpiarBusquedaMeta(); // También limpiar la búsqueda de Meta
  }

  importarOrden(): void {
    if (this.selectedMaquinaria && this.selectedMeta) {
      const formData = new FormData();
      const operador = this.searchForm.get('operador')?.value;
        
      formData.append('maquinaria_id', this.selectedMaquinaria.id.toString());
      formData.append('maquinaria_equipo', this.selectedMaquinaria.machinery_equipment || '');
      formData.append('maquinaria_marca', this.selectedMaquinaria.brand || '');
      formData.append('maquinaria_modelo', this.selectedMaquinaria.model || '');
      formData.append('maquinaria_serie', this.selectedMaquinaria.serial_number || '');
      formData.append('operador', operador);
        
      formData.append('meta_id', this.selectedMeta.idmeta);
      formData.append('meta_codigo', this.selectedMeta.codmeta);
      formData.append('meta_descripcion', this.selectedMeta.desmeta);

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
    } else {
      console.log('Faltan datos:', {
        maquinaria: !!this.selectedMaquinaria,
        meta: !!this.selectedMeta
      });
    }
  }

  getEstadoInfo(state: number): EstadoMaquinaria {
    return this.estadosMap.find(estado => estado.value === state) || 
           { value: state, label: 'Desconocido', color: 'warn' };
  }

  retryLoadEquipment(): void {
    this.loadMechanicalEquipment();
  }

  // Getter para verificar si el formulario de importación está completo
  get canImportOrder(): boolean {
    return !!(this.selectedMaquinaria && this.selectedMeta);
  }
}