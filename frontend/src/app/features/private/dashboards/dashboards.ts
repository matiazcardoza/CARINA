import { Component, OnInit, ChangeDetectorRef, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatCardModule } from '@angular/material/card';
import { MatTableModule } from '@angular/material/table';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatChipsModule } from '@angular/material/chips';
import { MatSelectModule } from '@angular/material/select';
import { MatTabsModule } from '@angular/material/tabs';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { MatBadgeModule } from '@angular/material/badge';
import { MatFormFieldModule } from '@angular/material/form-field';
import { FormBuilder, FormsModule, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { MatAutocomplete, MatAutocompleteModule } from '@angular/material/autocomplete';
import { Observable, startWith, map, catchError, of } from 'rxjs';
import { WorkLogElement } from '../daily-work-log/daily-work-log';
import { DailyWorkLogService } from '../../../services/DailyWorkLogService/daily-work-log-service';
import { ReportsServicesService } from '../../../services/ReportsServicesService/reports-services-service';
import { MatInputModule } from '@angular/material/input';
import { MatDialog } from '@angular/material/dialog';
import { MatMenuModule } from '@angular/material/menu';
import { MatDividerModule } from '@angular/material/divider';
import { MatTooltipModule } from '@angular/material/tooltip';
import { UsersService } from '../../../services/UsersService/users-service';



// Interfaz para datos falsos de firmas y evidencias
interface ParteDiarioFalso {
  id: number;
  estadoFirmas: {
    controlador: boolean;
    residente: boolean;
    supervisor: boolean;
  };
}

interface ResumenDashboard {
  totalHorasTrabajadas: number;
  totalCombustibleConsumido: number;
  partesCompletados: number;
  partesPendientes: number;
  porcentajeEficiencia: number;
}

@Component({
  selector: 'app-reports-and-dashboards',
  standalone: true,
  imports: [
    CommonModule,
    MatCardModule,
    MatTableModule,
    MatButtonModule,
    MatIconModule,
    MatChipsModule,
    MatSelectModule,
    MatTabsModule,
    MatProgressBarModule,
    MatBadgeModule,
    MatFormFieldModule,
    MatInputModule,
    FormsModule,
    ReactiveFormsModule,
    MatAutocompleteModule,
    MatMenuModule,
    MatDividerModule,
    MatTooltipModule
  ],
  templateUrl: './dashboards.html',
  styleUrl: './dashboards.css'
})
export class Dashboards implements OnInit {

  searchForm: FormGroup;
  filteredServicio!: Observable<WorkLogElement[]>;
  selectedServicio: WorkLogElement | null = null;
  servicioList: WorkLogElement[] = [];

  isLoading = false;
  errorMessage = '';

  // Datos reales de la API


  // Datos falsos para firmas y evidencias (temporal)
  datosFalsos: ParteDiarioFalso[] = [
    {
      id: 1,
      estadoFirmas: { controlador: true, residente: true, supervisor: false },
    },
    {
      id: 2,
      estadoFirmas: { controlador: true, residente: true, supervisor: true },
    },
    {
      id: 3,
      estadoFirmas: { controlador: true, residente: false, supervisor: false },
    }
  ];

  // Datos falsos para el dashboard
  resumenDashboard: ResumenDashboard = {
    totalHorasTrabajadas: 245.5,
    totalCombustibleConsumido: 1250.75,
    partesCompletados: 18,
    partesPendientes: 5,
    porcentajeEficiencia: 78.2
  };

  // Columnas actualizadas para la tabla
  displayedColumns: string[] = [
    'estado',
    'servicio',
    'horasTrabajadas',
    'combustibleConsumido',
    'estadoFirmas',
    'evidencias',
    'acciones'
  ];

  constructor(
    private fb: FormBuilder,
    private cdr: ChangeDetectorRef,
    private dailyWorkLogService: DailyWorkLogService,
    private reportsServicesService: ReportsServicesService,
    private usersService: UsersService,
  ) {
    this.searchForm = this.fb.group({
      servicioSearch: ['']
    });
  }

  private dialog = inject(MatDialog);

  ngOnInit(): void {
    this.runUserImport();
    this.calcularResumenDashboard();
  }

  runUserImport(): void {
    this.usersService.importUsers().subscribe({
        next: (response) => {
            console.log('Importación de usuarios finalizada.', response);
        },
        error: (error) => {
            console.error('La importación de usuarios falló al ingresar:', error);
        }
    });
    this.usersService.importControlador().subscribe({
        next: (response) => {
            console.log('Importación de controlador finalizada.', response);
        },
        error: (error) => {
            console.error('La importación de usuarios falló al ingresar:', error);
        }
    });
  }

  // Método para obtener el texto del estado
  obtenerTextoEstado(state: number): string {
    switch (state) {
      case 1:
        return 'Máquina Seca';
      case 2:
        return 'Máquina Servida';
      case 3:
        return 'Equipo Mecánico';
      default:
        return 'Estado Desconocido';
    }
  }

  // Método para obtener el color del estado
  obtenerColorEstado(state: number): string {
    switch (state) {
      case 1:
        return 'warn'; // Amarillo/naranja
      case 2:
        return 'primary'; // Azul
      case 3:
        return 'accent'; // Verde/otro color
      default:
        return 'basic';
    }
  }

  // Método para convertir tiempo trabajado a horas decimales
  convertirTiempoAHoras(tiempo: string): number {
    if (!tiempo) return 0;

    const partes = tiempo.split(':');
    if (partes.length !== 3) return 0;

    const horas = parseInt(partes[0]);
    const minutos = parseInt(partes[1]);
    const segundos = parseInt(partes[2]);

    return horas + (minutos / 60) + (segundos / 3600);
  }


  // Mantener método original para datos falsos del dashboard inicial
  calcularResumenDashboard(): void {
    // Este método se mantiene para el dashboard inicial con datos falsos
    // Se puede eliminar cuando tengas datos reales para el dashboard
  }

  // Métodos para datos falsos de firmas (temporal)
  obtenerDatosFalsos(id: number): ParteDiarioFalso {
    const datosFalso = this.datosFalsos.find(d => d.id === (id % 3) + 1);
    return datosFalso || this.datosFalsos[0];
  }

  todasLasFirmasCompletas(estadoFirmas: any): boolean {
    return estadoFirmas.controlador && estadoFirmas.residente && estadoFirmas.supervisor;
  }

  obtenerEstadoFirmas(estadoFirmas: any): string {
    const firmasCompletas = [
      estadoFirmas.controlador,
      estadoFirmas.residente,
      estadoFirmas.supervisor
    ].filter(firma => firma).length;

    if (firmasCompletas === 3) return 'Completo';
    if (firmasCompletas === 0) return 'Pendiente';
    return 'Parcial';
  }

  obtenerColorEstadoFirmas(estadoFirmas: any): string {
    const estado = this.obtenerEstadoFirmas(estadoFirmas);
    switch (estado) {
      case 'Completo': return 'primary';
      case 'Parcial': return 'warn';
      case 'Pendiente': return 'accent';
      default: return 'accent';
    }
  }



  descargarReporte(): void {
    console.log('Descargando reporte...');
    // Aquí implementarías la funcionalidad de descarga
  }
  limpiarSelector(): void {
    this.searchForm.get('servicioSearch')?.setValue('');
    this.selectedServicio = null;
    this.resumenDashboard = {
      totalHorasTrabajadas: 245.5,
      totalCombustibleConsumido: 1250.75,
      partesCompletados: 18,
      partesPendientes: 5,
      porcentajeEficiencia: 78.2
    };
    this.cdr.detectChanges();
  }

  displayServicio(servicio: WorkLogElement): string {
    return servicio ? `${servicio.goal_project || 'N/A'} - ${servicio.goal_detail}` : '';
  }

  private _filterServicio(value: string): WorkLogElement[] {
    if (!value) {
      return this.servicioList;
    }

    const filterValue = value.toLowerCase();
    return this.servicioList.filter(servicio =>
      servicio.goal_project?.toLowerCase().includes(filterValue) ||
      servicio.goal_detail?.toLowerCase().includes(filterValue)
    );
  }

  obtenerColorBotonFirmas(parteId: any): string {
    const estadoFirmas = this.obtenerDatosFalsos(parteId).estadoFirmas;
    const todasFirmadas = estadoFirmas.controlador && estadoFirmas.residente && estadoFirmas.supervisor;

    if (todasFirmadas) {
      return 'primary';
    } else if (estadoFirmas.controlador || estadoFirmas.residente || estadoFirmas.supervisor) {
      return 'accent';
    } else {
      return 'warn';
    }
  }
}
