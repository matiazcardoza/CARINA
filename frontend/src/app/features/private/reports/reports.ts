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
import { WorkLogElement } from '../../../features/private/daily-work-log/daily-work-log';
import { DailyWorkLogService, ValorationData } from '../../../services/DailyWorkLogService/daily-work-log-service';
import { ReportsServicesService } from '../../../services/ReportsServicesService/reports-services-service';
import { MatInputModule } from '@angular/material/input';
import { MatDialog } from '@angular/material/dialog';
import { ViewEvidence } from './view/view-evidence/view-evidence';
import { MatMenuModule } from '@angular/material/menu';
import { MatDividerModule } from '@angular/material/divider';
import { MatTooltipModule } from '@angular/material/tooltip';
import { AuthService } from '../../../services/AuthService/auth';
import { HasPermissionDirective } from '../../../shared/directives/permission.directive';
import { Router } from '@angular/router';
import { ReportValorized } from './view/report-valorized/report-valorized';

import { ReportsId } from './reports-id/reports-id';

export interface WorkLogDataElement {
  id: number;
  description: string;
  time_worked: string;
  fuel_consumed: string;
  state: number;
  operator: string;
  created_at: string;
  updated_at: string;
  goal_detail: string;
  goal_project: string;
  goal_id: number;
  mechanical_equipment_id: number;
  order_id: number | null;
}

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
    MatTooltipModule,
    HasPermissionDirective
  ],
  templateUrl: './reports.html',
  styleUrl: './reports.css'
})
export class Reports implements OnInit {

  searchForm: FormGroup;
  filteredServicio!: Observable<WorkLogElement[]>;
  selectedServicio: WorkLogElement | null = null;
  servicioList: WorkLogElement[] = [];
  valorationData: ValorationData | null = null;

  isLoading = false;
  errorMessage = '';

  isDownloading = false;

  // Datos reales de la API
  partesDiariosReales: WorkLogDataElement[] = [];

  // Columnas actualizadas para la tabla
  displayedColumns: string[] = [
    'estado',
    'servicio',
    'horasTrabajadas',
    'combustibleConsumido',
    'evidencias',
    'acciones'
  ];

  constructor(
    private fb: FormBuilder,
    private cdr: ChangeDetectorRef,
    private dailyWorkLogService: DailyWorkLogService,
    private reportsServicesService: ReportsServicesService,
    private authService: AuthService
  ) {
    this.searchForm = this.fb.group({
      servicioSearch: ['']
    });
  }

  private dialog = inject(MatDialog);
  private router = inject(Router);

  canAccessDashboard: boolean = false;
  canAccessReports: boolean = false;
  canGenerateReports: boolean = false;
  canEditWorkLog: boolean = false;
  canDeleteWorkLog: boolean = false;

  ngOnInit(): void {
    this.loadServices();
  }

  loadServices(): void {
    this.isLoading = true;
    this.errorMessage = '';

    this.dailyWorkLogService.getSelectedServiceData()
      .pipe(
        catchError(error => {
          console.error('Error loading mechanical equipment:', error);
          this.errorMessage = 'Error al cargar la servicio. Por favor, intente nuevamente.';
          return of([]);
        })
      )
      .subscribe(service => {
        this.servicioList = service;
        this.isLoading = false;
        this.filteredServicio = this.searchForm.get('servicioSearch')!.valueChanges.pipe(
          startWith(''),
          map(value => this._filterServicio(typeof value === 'string' ? value : value?.goal_detail || ''))
        );
        this.cdr.detectChanges();
      });
  }

  getDailyPartsData(servicio: WorkLogElement): void {
    this.isLoading = true;
    this.errorMessage = '';

    this.dailyWorkLogService.getDailyPartData(servicio.goal_id)
    .pipe(
      catchError(error => {
        console.error('Error al cargar los partes diarios:', error);
        this.errorMessage = 'Error al cargar los partes diarios. Por favor, intente nuevamente.';
        return of({ valoration: null, data: [] });
      })
    )
    .subscribe((response: { valoration: ValorationData | null, data: WorkLogDataElement[] }) => {
      console.log('Datos recibidos:', response);
      console.log('Valoración:', response.valoration);

      this.partesDiariosReales = response.data;
      this.valorationData = response.valoration;

      this.isLoading = false;
      this.cdr.detectChanges();
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

  viewEvidenceData(id: number, servicio: WorkLogElement){
    const dialogRef = this.dialog.open(ViewEvidence, {
      width: '900px',
      maxWidth: '90vw',
      maxHeight: '90vh',
      data: {
        ServiceId: id
      }
    });
    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.getDailyPartsData(servicio);
      }
    });
  }

  descargarReporte(): void {
    console.log('Descargando reporte...');
  }
  limpiarSelector(): void {
    this.searchForm.get('servicioSearch')?.setValue('');
    this.selectedServicio = null;
    this.partesDiariosReales = [];
    this.cdr.detectChanges();
  }

  displayServicio(servicio: WorkLogElement): string {
    return servicio ? `${servicio.goal_project || 'N/A'} - ${servicio.goal_detail}` : '';
  }

  onServicioSelected(servicio: WorkLogElement): void {
    this.selectedServicio = servicio;
    this.getDailyPartsData(servicio);
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

  downloadAllCompletedDailyParts(serviceId: number, stateValorized: number): void {
    this.isDownloading = true; 
    this.reportsServicesService.downloadAllCompletedDailyParts(serviceId, stateValorized).subscribe({
      next: (response: Blob) => {
        const fileURL = URL.createObjectURL(response);
        window.open(fileURL, '_blank');
        this.isDownloading = false; 
      },
      error: () => {
        this.errorMessage = 'Error al generar el PDF. Por favor, intenta nuevamente.';
        this.isDownloading = false;
      }
    });
  }

  closeService(serviceId: number) {
    if (confirm('¿Está seguro de que desea cerrar este servicio? Esta acción no se puede deshacer.')) {
      this.reportsServicesService.closeService(serviceId).subscribe({
        next: () => {
          if (this.selectedServicio) {
            this.getDailyPartsData(this.selectedServicio);
          }
          this.cdr.detectChanges();
        },
        error: (error) => {
          console.error('Error al cerrar servicio:', error);
          this.errorMessage = 'Error al cerrar el servicio. Por favor, intenta nuevamente.';
        }
      });
    }
  }

  openValorizedDialog(id: number) {
    if (!this.valorationData) {
      this.errorMessage = 'No hay datos de valorización disponibles para este servicio.';
      return;
    }

    const dialogRef = this.dialog.open(ReportValorized, {
      width: '100vw',
      height: '100vh',
      maxWidth: '100vw',
      maxHeight: '100vh',
      panelClass: ['maximized-dialog-panel', 'no-scroll-dialog'],
      disableClose: false,
      hasBackdrop: true,
      backdropClass: 'maximized-dialog-backdrop',
      autoFocus: false,
      restoreFocus: false,
      data: {
        valorationData: this.valorationData,
        serviceId: id
      }
    });

    // Bloquea el scroll del body y html
    setTimeout(() => {
      document.body.style.overflow = 'hidden';
      document.documentElement.style.overflow = 'hidden';
    }, 0);

    dialogRef.afterClosed().subscribe(() => {
      // Restaurar scroll
      document.body.style.overflow = '';
      document.documentElement.style.overflow = '';

      // Recargar datos si es necesario
      if (this.selectedServicio) {
        this.getDailyPartsData(this.selectedServicio);
      }

      this.cdr.detectChanges();
    });
  }


  navigateToReportsId(id: number, state: number) {
    this.router.navigate(['/reports/reports-id', id, state]);
  }
}
