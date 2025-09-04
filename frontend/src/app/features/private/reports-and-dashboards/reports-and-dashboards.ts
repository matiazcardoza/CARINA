import { Component, OnInit, ChangeDetectorRef, Inject } from '@angular/core';
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
import { WorkLogIdElement } from '../daily-work-log/daily-work-log-id/daily-work-log-id';
import { DailyWorkLogService } from '../../../services/DailyWorkLogService/daily-work-log-service';
import { MatInputModule } from '@angular/material/input';

interface ParteDiario {
  id: number;
  fecha: Date;
  servicio: string;
  horaInicio: string;
  horaFin: string;
  horasTrabajadas: number;
  combustibleInicial: number;
  combustibleFinal: number;
  combustibleConsumido: number;
  estadoFirmas: {
    controlador: boolean;
    residente: boolean;
    supervisor: boolean;
  };
  evidencias: number;
  proyecto: string;
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
    MatAutocompleteModule
  ],
  templateUrl: './reports-and-dashboards.html',
  styleUrl: './reports-and-dashboards.css'
})
export class ReportsAndDashboards implements OnInit {

  searchForm: FormGroup;
  filteredServicio!: Observable<WorkLogElement[]>;
  selectedServicio: WorkLogElement | null = null;
  servicioList: WorkLogElement[] = [];

  isLoading = false;
  errorMessage = '';


  // Datos falsos para el dashboard
  resumenDashboard: ResumenDashboard = {
    totalHorasTrabajadas: 245.5,
    totalCombustibleConsumido: 1250.75,
    partesCompletados: 18,
    partesPendientes: 5,
    porcentajeEficiencia: 78.2
  };

  // Datos falsos para los partes diarios
  partesDiarios: ParteDiario[] = [
    {
      id: 1,
      fecha: new Date('2024-01-15'),
      servicio: 'Excavadora CAT 320D',
      horaInicio: '07:00',
      horaFin: '17:00',
      horasTrabajadas: 9.5,
      combustibleInicial: 180.5,
      combustibleFinal: 95.2,
      combustibleConsumido: 85.3,
      estadoFirmas: {
        controlador: true,
        residente: true,
        supervisor: false
      },
      evidencias: 4,
      proyecto: 'Construcción Puente Norte'
    },
    {
      id: 2,
      fecha: new Date('2024-01-14'),
      servicio: 'Volquete Mercedes 2635',
      horaInicio: '06:30',
      horaFin: '16:30',
      horasTrabajadas: 10.0,
      combustibleInicial: 200.0,
      combustibleFinal: 125.8,
      combustibleConsumido: 74.2,
      estadoFirmas: {
        controlador: true,
        residente: true,
        supervisor: true
      },
      evidencias: 6,
      proyecto: 'Pavimentación Av. Principal'
    },
    {
      id: 3,
      fecha: new Date('2024-01-13'),
      servicio: 'Motoniveladora John Deere 670G',
      horaInicio: '08:00',
      horaFin: '18:00',
      horasTrabajadas: 9.0,
      combustibleInicial: 150.0,
      combustibleFinal: 75.5,
      combustibleConsumido: 74.5,
      estadoFirmas: {
        controlador: true,
        residente: false,
        supervisor: false
      },
      evidencias: 3,
      proyecto: 'Mejoramiento Carretera Sur'
    },
    {
      id: 4,
      fecha: new Date('2024-01-12'),
      servicio: 'Compactadora Dynapac CA25',
      horaInicio: '07:30',
      horaFin: '15:30',
      horasTrabajadas: 8.0,
      combustibleInicial: 120.0,
      combustibleFinal: 65.0,
      combustibleConsumido: 55.0,
      estadoFirmas: {
        controlador: true,
        residente: true,
        supervisor: true
      },
      evidencias: 5,
      proyecto: 'Construcción Puente Norte'
    },
    {
      id: 5,
      fecha: new Date('2024-01-11'),
      servicio: 'Retroexcavadora JCB 3CX',
      horaInicio: '09:00',
      horaFin: '17:00',
      horasTrabajadas: 7.5,
      combustibleInicial: 100.0,
      combustibleFinal: 42.3,
      combustibleConsumido: 57.7,
      estadoFirmas: {
        controlador: true,
        residente: true,
        supervisor: false
      },
      evidencias: 2,
      proyecto: 'Pavimentación Av. Principal'
    }
  ];

  // Columnas para la tabla de partes diarios
  displayedColumns: string[] = [
    'fecha', 
    'servicio', 
    'horasTrabajadas', 
    'combustibleConsumido', 
    'estadoFirmas', 
    'evidencias', 
    'proyecto',
    'acciones'
  ];

  // Selector de proyecto
  proyectoSeleccionado: string = '';
  proyectosDisponibles: string[] = [
    'Todos los proyectos',
    'Construcción Puente Norte',
    'Pavimentación Av. Principal',
    'Mejoramiento Carretera Sur'
  ];

  constructor(
    private fb: FormBuilder,
    private cdr: ChangeDetectorRef,
    private dailyWorkLogService: DailyWorkLogService
  ) {
    this.searchForm = this.fb.group({
      maquinariaSearch: ['']
    });
  }

  ngOnInit(): void {
    // Inicialización del componente
    
    this.loadServices();
    this.calcularResumenDashboard();
  }

  loadServices(): void {
    this.isLoading = true;
    this.errorMessage = '';
    
    this.dailyWorkLogService.getSelectedServiceData()
      .pipe(
        catchError(error => {
          console.error('Error loading mechanical equipment:', error);
          this.errorMessage = 'Error al cargar la maquinaria. Por favor, intente nuevamente.';
          return of([]);
        })
      )
      .subscribe(service => {
        this.servicioList = service;
        this.isLoading = false;
        this.filteredServicio = this.searchForm.get('maquinariaSearch')!.valueChanges.pipe(
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
        return of([]);
      })
    )
    .subscribe((data: WorkLogIdElement[]) => {
      console.log('Estos son los datos que llegan:', data);
      this.isLoading = false;
    });
  }

  calcularResumenDashboard(): void {
    // Simular cálculos del dashboard basado en los datos
    this.resumenDashboard.totalHorasTrabajadas = this.partesDiarios
      .reduce((total, parte) => total + parte.horasTrabajadas, 0);
    
    this.resumenDashboard.totalCombustibleConsumido = this.partesDiarios
      .reduce((total, parte) => total + parte.combustibleConsumido, 0);
    
    this.resumenDashboard.partesCompletados = this.partesDiarios
      .filter(parte => this.todasLasFirmasCompletas(parte.estadoFirmas)).length;
    
    this.resumenDashboard.partesPendientes = this.partesDiarios.length - this.resumenDashboard.partesCompletados;
    
    this.resumenDashboard.porcentajeEficiencia = 
      (this.resumenDashboard.partesCompletados / this.partesDiarios.length) * 100;
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

  verDetalleParte(parte: ParteDiario): void {
    console.log('Ver detalle del parte:', parte);
    // Aquí implementarías la navegación al detalle del parte
  }

  descargarReporte(): void {
    console.log('Descargando reporte...');
    // Aquí implementarías la funcionalidad de descarga
  }

  onProyectoChange(): void {
    console.log('Proyecto seleccionado:', this.proyectoSeleccionado);
    // Aquí implementarías la lógica de filtrado por proyecto
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
}