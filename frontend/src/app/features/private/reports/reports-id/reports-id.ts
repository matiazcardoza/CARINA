import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ReportsServicesService } from '../../../../services/ReportsServicesService/reports-services-service';

export interface LiquidationElement {
  equipment: any | null;
  request: [];
  authorization: [];
  liquidation: [];
}

interface DocumentStatus {
  solicitud: boolean;
  autorizacion: boolean;
  liquidacion: boolean;
}

interface DailyWork {
  fecha: string;
  hmTrabajadas: string;
  hmEquivalente: number;
  combustible: number;
  diasTrabajados: number;
  costoHora: number;
  importeTotal: number;
}

@Component({
  selector: 'app-reports-id',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './reports-id.html',
  styleUrl: './reports-id.css'
})
export class ReportsId implements OnInit {
  reportId: number = 0;
  state: number = 0;
  errorMessage: string | null = null;
  activeDocument: 'solicitud' | 'autorizacion' | 'liquidacion' = 'solicitud';

  //implementacion de componente
  isLoading = false;
  error: string | null = null;
  equipmentData: any | null = null;


  // Estado de documentos generados
  documentStatus: DocumentStatus = {
    solicitud: false,
    autorizacion: false,
    liquidacion: false
  };

  // Información general
  informacionGeneral = {
    orden: '1137',
    fechaSolicitud: '13/5/2025',
    periodoInicio: '17/5/2025',
    periodoFin: '31/5/2025',
    duracionDias: 13,
    horaSalida: '7:00 a.m.',
    horaRetorno: '5:00 p.m.'
  };

  // Información del equipo y operador
  equipoOperador = {
    equipo: 'EXCAVADORA PC 360',
    marca: 'KOMATSU',
    placa: 'PC 360',
    operador: 'HUGO PEDRO RAMOS CALAMULLO'
  };

  // Información del proyecto
  proyecto = {
    organica: 'SUB GERENCIA DE EJECUCION DE PROYECTOS',
    direccion: 'JR. MOQUEGUA N° 269-A',
    nombre: 'MEJORAMIENTO DE LA CARRETERA (EMP 34B) AZANGARO - (EMP PU-102) JILA PURINA DE LOS DISTRITO DE AZANGARO - DISTRITO DE TIRAPATA - PROVINCIA DE AZANGARO - REGION PUNO',
    objetivo: 'MEJORAMIENTO DE LA CARRETERA (EMP 34B) AZANGARO - (EMP PU-102) JILA PURINA DE LOS DISTRITO DE AZANGARO - DISTRITO DE TIRAPATA - PROVINCIA DE AZANGARO - REGION PUNO'
  };

  // Resumen de costos
  resumenCostos = {
    horasTrabajadas: '89:50',
    horasEquivalentes: 89.83,
    diasTrabajados: 13,
    costoPorHora: 285.00,
    costoPorDia: 1969.35,
    importeTotal: 25601.55,
    importeTotalLetras: 'VEINTICINCO MIL SEISCIENTOS UNO CON 55/100 SOLES',
    combustibleTotal: 665.00
  };

  // Detalle de trabajo diario
  trabajoDiario: DailyWork[] = [
    { fecha: '13/5/2025', hmTrabajadas: '-', hmEquivalente: 0, combustible: 0, diasTrabajados: 0, costoHora: 285.00, importeTotal: 0 },
    { fecha: '14/5/2025', hmTrabajadas: '-', hmEquivalente: 0, combustible: 0, diasTrabajados: 0, costoHora: 285.00, importeTotal: 0 },
    { fecha: '15/5/2025', hmTrabajadas: '-', hmEquivalente: 0, combustible: 0, diasTrabajados: 0, costoHora: 285.00, importeTotal: 0 },
    { fecha: '16/5/2025', hmTrabajadas: '-', hmEquivalente: 0, combustible: 0, diasTrabajados: 0, costoHora: 285.00, importeTotal: 0 },
    { fecha: '17/5/2025', hmTrabajadas: '04:10', hmEquivalente: 4.17, combustible: 30.00, diasTrabajados: 1, costoHora: 285.00, importeTotal: 1188.45 },
    { fecha: '18/5/2025', hmTrabajadas: '-', hmEquivalente: 0, combustible: 0, diasTrabajados: 0, costoHora: 285.00, importeTotal: 0 },
    { fecha: '19/5/2025', hmTrabajadas: '07:00', hmEquivalente: 7.00, combustible: 70.00, diasTrabajados: 1, costoHora: 285.00, importeTotal: 1995.00 },
    { fecha: '20/5/2025', hmTrabajadas: '07:00', hmEquivalente: 7.00, combustible: 45.00, diasTrabajados: 1, costoHora: 285.00, importeTotal: 1995.00 },
    { fecha: '21/5/2025', hmTrabajadas: '08:10', hmEquivalente: 8.17, combustible: 50.00, diasTrabajados: 1, costoHora: 285.00, importeTotal: 2328.45 },
    { fecha: '22/5/2025', hmTrabajadas: '08:10', hmEquivalente: 8.17, combustible: 60.00, diasTrabajados: 1, costoHora: 285.00, importeTotal: 2328.45 },
    { fecha: '23/5/2025', hmTrabajadas: '07:10', hmEquivalente: 7.17, combustible: 60.00, diasTrabajados: 1, costoHora: 285.00, importeTotal: 2043.45 },
    { fecha: '24/5/2025', hmTrabajadas: '03:40', hmEquivalente: 3.67, combustible: 30.00, diasTrabajados: 1, costoHora: 285.00, importeTotal: 1045.95 },
    { fecha: '25/5/2025', hmTrabajadas: '-', hmEquivalente: 0, combustible: 0, diasTrabajados: 0, costoHora: 285.00, importeTotal: 0 },
    { fecha: '26/5/2025', hmTrabajadas: '07:10', hmEquivalente: 7.17, combustible: 40.00, diasTrabajados: 1, costoHora: 285.00, importeTotal: 2043.45 },
    { fecha: '27/5/2025', hmTrabajadas: '08:30', hmEquivalente: 8.50, combustible: 60.00, diasTrabajados: 1, costoHora: 285.00, importeTotal: 2422.50 },
    { fecha: '28/5/2025', hmTrabajadas: '07:30', hmEquivalente: 7.50, combustible: 60.00, diasTrabajados: 1, costoHora: 285.00, importeTotal: 2137.50 },
    { fecha: '29/5/2025', hmTrabajadas: '08:30', hmEquivalente: 8.50, combustible: 60.00, diasTrabajados: 1, costoHora: 285.00, importeTotal: 2422.50 },
    { fecha: '30/5/2025', hmTrabajadas: '08:20', hmEquivalente: 8.33, combustible: 70.00, diasTrabajados: 1, costoHora: 285.00, importeTotal: 2374.05 },
    { fecha: '31/5/2025', hmTrabajadas: '04:30', hmEquivalente: 4.50, combustible: 30.00, diasTrabajados: 1, costoHora: 285.00, importeTotal: 1282.50 }
  ];

  constructor(
    private route: ActivatedRoute,
    private reportsServicesService: ReportsServicesService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      this.reportId = +params['id'];
      this.state = +params['state'];
      console.log('Report ID:', this.reportId);
      console.log('State:', this.state);
    });
    this.loadLiquidationData();
  }

  loadLiquidationData(): void {
    console.log('Loading liquidation data for report ID:', this.reportId);
    Promise.resolve().then(() => {
      this.isLoading = true;
      this.error = null;
      const reportId = this.reportId;
      console.log('Report ID in function loadLiquidationData:', reportId);
      this.reportsServicesService.getLiquidationData(reportId)
        .subscribe({
          next: (response) => {
            console.log('Liquidation data response:', response);
            this.equipmentData = response.equipment;
            console.log('Equipment data:', this.equipmentData);
            this.isLoading = false;
            this.cdr.detectChanges();
          },
          error: (error) => {
            console.error('Error loading users:', error);
            this.error = 'Error al cargar los datos. Por favor, intenta nuevamente.';
            this.isLoading = false;
            this.cdr.detectChanges();
          }
        });
    });
  }

  changeDocument(type: 'solicitud' | 'autorizacion' | 'liquidacion'): void {
    this.activeDocument = type;
  }

  generateRequest(): void {
    const reportId = this.reportId;
    this.reportsServicesService.generateRequest(reportId).subscribe({
      next: (response: Blob) => {
        this.documentStatus.solicitud = true;
        const fileURL = URL.createObjectURL(response);
        window.open(fileURL, '_blank');
      },
      error: () => {
        this.errorMessage = 'Error al generar el PDF. Por favor, intenta nuevamente.';
      }
    });
  }

  generarLiquidacion(): void {
    console.log('Generar liquidación');
    this.documentStatus.liquidacion = true;
    // Aquí iría la llamada al servicio
  }

  generarAutorizacion(): void {
    console.log('Generar autorización');
    this.documentStatus.autorizacion = true;
    // Aquí iría la llamada al servicio
  }

  getBadgeClass(state: number): string {
    switch(state) {
      case 1: return 'badge-seca';
      case 2: return 'badge-servida';
      case 3: return 'badge-mecanico';
      default: return 'badge-default';
    }
  }

  getStateText(state: number): string {
    switch(state) {
      case 1: return 'Máquina seca';
      case 2: return 'Máquina servida';
      case 3: return 'Equipo mecánico';
      default: return 'Estado desconocido';
    }
  }

  getDocumentIcon(type: string): string {
    switch(type) {
      case 'solicitud': return '📋';
      case 'autorizacion': return '✅';
      case 'liquidacion': return '💰';
      default: return '📄';
    }
  }
}