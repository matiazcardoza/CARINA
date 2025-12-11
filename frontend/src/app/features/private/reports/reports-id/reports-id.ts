import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { Observable, throwError } from 'rxjs';
import { tap, catchError } from 'rxjs/operators';
import { CommonModule } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { ReportsServicesService } from '../../../../services/ReportsServicesService/reports-services-service';
import { MatTooltipModule } from '@angular/material/tooltip';
import { AlertConfirm } from '../../../../components/alert-confirm/alert-confirm';
import { MatDialog } from '@angular/material/dialog';

export interface LiquidationElement {
  equipment: any | null;
  request: any | null;
  auth: any | null;
  liquidation: any | null;
}

interface DocumentStatus {
  solicitud: boolean;
  autorizacion: boolean;
  liquidacion: boolean;
}

@Component({
  selector: 'app-reports-id',
  standalone: true,
  imports: [CommonModule, MatTooltipModule],
  templateUrl: './reports-id.html',
  styleUrl: './reports-id.css'
})
export class ReportsId implements OnInit {
  reportId: number = 0;
  state: number = 0;
  errorMessage: string | null = null;
  activeDocument: 'solicitud' | 'autorizacion' | 'liquidacion' = 'solicitud';
  today: Date = new Date();

  //implementacion de componente
  isLoading = false;
  error: string | null = null;
  equipmentData: any | null = null;
  requestData: any | null = null;
  authData: any | null = null;
  liquidationData: any | null = null;

  // Estado de documentos generados
  documentStatus: DocumentStatus = {
    solicitud: false,
    autorizacion: false,
    liquidacion: false
  };

  isGeneratingDocs: boolean = false;
  liquidationSaved: boolean = false;

  adjustmentHistory: any[] = [];
  selectedAdjustmentId: number | null = null;
  isHistoryLoading: boolean = false;

  constructor(
    private route: ActivatedRoute,
    private reportsServicesService: ReportsServicesService,
    private cdr: ChangeDetectorRef,
    private dialog: MatDialog
  ) {}

  //propiedades de edit
  editMode: boolean = false;
  editedAuthData: any = null;
  editingCell: { rowIndex: number, field: string } | null = null;
  hasUnsavedChanges: boolean = false;

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      this.reportId = +params['id'];
      this.state = +params['state'];
      console.log('Report ID:', this.reportId);
      console.log('State:', this.state);
    });
    this.isLoading = true;
    this.loadLiquidationData();
    this.loadAdjustmentHistory();
  }

  loadLiquidationData(): void {
    this.error = null;
    const reportId = this.reportId;
    
    this.reportsServicesService.getLiquidationData(reportId)
      .subscribe({
        next: (response) => {
          console.log('Liquidation data response:', response);
          this.equipmentData = response.equipment;
          this.requestData = response.request;
          this.authData = response.auth;
          this.liquidationData = response.liquidation;
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
  }

  loadAdjustmentHistory(): void {
    this.isHistoryLoading = true;
    const serviceId = this.reportId;
    this.reportsServicesService.getAdjustedLiquidationData(serviceId)
      .subscribe({
        next: (history) => {
          this.adjustmentHistory = history;
          this.isHistoryLoading = false;
          this.cdr.detectChanges();
        },
        error: (error) => {
          console.error('Error loading adjustment history:', error);
          this.isHistoryLoading = false;
          this.cdr.detectChanges();
        }
      });
  }

  loadAdjustmentData(adjustment: any): void {
    if (this.hasUnsavedChanges) {
      const confirm = window.confirm(
        '¿Tienes cambios sin guardar. ¿Deseas descartarlos y cargar este registro?'
      );
      if (!confirm) return;
    }
    this.selectedAdjustmentId = adjustment.id;
    const adjustedData = adjustment.adjusted_data;
    
    this.equipmentData = adjustedData.equipment;
    this.requestData = adjustedData.request;
    console.log('Adjusted auth data to load:', this.requestData);
    this.authData = adjustedData.auth;
    this.liquidationData = adjustedData.liquidation;
    this.editMode = false;
    this.editedAuthData = null;
    this.hasUnsavedChanges = false;
    this.editingCell = null;
    this.liquidationSaved = true;

    this.cdr.detectChanges();
  }

  loadCurrentVersion(): void {
    if (this.hasUnsavedChanges) {
      const confirm = window.confirm(
        'Tienes cambios sin guardar. ¿Deseas descartarlos y cargar la versión actual?'
      );
      if (!confirm) return;
    }
    this.selectedAdjustmentId = null;
    this.liquidationSaved = false;
    this.loadLiquidationData();
  }

  changeDocument(type: 'solicitud' | 'autorizacion' | 'liquidacion'): void {
    this.activeDocument = type;
  }

  generateRequest(): void {
    const formDataRequest = {
      serviceId: this.reportId,
      equipment: this.equipmentData,
      request: this.requestData
    };
    this.reportsServicesService.generateRequest(formDataRequest).subscribe({
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

  generateAuth() {
    const formDataAuth = {
      serviceId: this.reportId,
      equipment: this.equipmentData,
      request: this.requestData,
      auth: this.authData
    };
    this.reportsServicesService.generateAuth(formDataAuth).subscribe({
      next: (response: Blob) => {
        const fileURL = URL.createObjectURL(response);
        window.open(fileURL, '_blank');
      },
      error: () => {
        this.errorMessage = 'Error al generar el PDF. Por favor, intenta nuevamente.';
      }
    });
  }

  generateLiquidation() {
    const formDataLiquidation = {
      serviceId: this.reportId,
      equipment: this.equipmentData,
      request: this.requestData,
      auth: this.authData,
      liquidation: this.liquidationData
    };

    this.reportsServicesService.generateLiquidation(formDataLiquidation).subscribe({
      next: (response: Blob) => {
        const fileURL = URL.createObjectURL(response);
        window.open(fileURL, '_blank');
      },
      error: () => {
        this.errorMessage = 'Error al generar el PDF. Por favor, intenta nuevamente.';
      }
    });
  }

  generateDocuments(): void {
    if (this.editMode) {
      alert('Debe salir del modo edición antes de generar documentos.');
      return;
    }

    if (this.hasUnsavedChanges) {
      alert('Debe aplicar y guardar los cambios antes de generar documentos.');
      return;
    }

    if (!this.liquidationSaved) {
      alert('Debe guardar la liquidación antes de generar los documentos.');
      return;
    }

    this.isGeneratingDocs = true;
    this.generateRequest();
    this.generateAuth();
    this.generateLiquidation();
    this.isGeneratingDocs = false;
  }

  saveEditChanges(): void {
    if (!this.editedAuthData) {
      alert('No hay cambios para aplicar.');
      return;
    }
    this.authData = JSON.parse(JSON.stringify(this.editedAuthData));
    this.editMode = false;
    this.editedAuthData = null;
    this.editingCell = null;
    this.hasUnsavedChanges = false;
    this.liquidationSaved = false;
    this.cdr.detectChanges();
    const dialogRef = this.dialog.open(AlertConfirm, {
      width: '450px',
      data: {
        title: 'Cambios aplicados',
        message: 'Debe guardar la liquidación para que los cambios se reflejen en el documento.',
        content: `cambios aplicados correctamente.`,
        confirmText: 'Aceptar',
        type: 'success'
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      // Acción después de cerrar el diálogo si es necesario
    });
  }

  cancelEdit(): void {
    if (this.hasUnsavedChanges) {
      const confirmar = confirm('Tiene cambios sin aplicar. ¿Desea descartarlos?');
      if (!confirmar) return;
    }
    this.editMode = false;
    this.editedAuthData = null;
    this.editingCell = null;
    this.hasUnsavedChanges = false;
    this.cdr.detectChanges();
  }

  saveLiquidation(): void {
    if (!this.authData) {
      alert('No hay datos para guardar. Espera a que se carguen los datos.');
      return;
    }
    if (this.editMode) {
      alert('Debe aplicar o cancelar los cambios en modo edición antes de guardar la liquidación.');
      return;
    }
    if (this.hasUnsavedChanges) {
      alert('Debe aplicar los cambios en modo edición antes de guardar la liquidación.');
      return;
    }
    if (this.selectedAdjustmentId !== null && this.liquidationSaved) {
      const confirmar = confirm('¿Desea actualizar el registro seleccionado del historial?');
      if (!confirmar) return;
    }
    if (this.liquidationSaved && this.selectedAdjustmentId === null) {
      const confirmar = confirm('La liquidación ya está guardada. ¿Desea crear un nuevo registro?');
      if (!confirmar) return;
    }

    const changesData = {
      serviceId: this.reportId,
      adjustmentId: this.selectedAdjustmentId,
      equipment: this.equipmentData,
      request: this.requestData,
      auth: this.authData,
      liquidation: this.liquidationData
    };

    this.isLoading = true;

    this.reportsServicesService.saveAuthChanges(changesData).subscribe({
      next: (response) => {
        if (response.data?.record) {
          this.requestData.record = response.data.record;
        }
        if (response.data?.adjustment_id) {
          this.selectedAdjustmentId = response.data.adjustment_id;
        }
        this.liquidationSaved = true;
        this.isLoading = false;
        this.loadAdjustmentHistory();
        this.cdr.detectChanges();
        const dialogRef = this.dialog.open(AlertConfirm, {
          width: '450px',
          data: {
            title: 'Liquidación guardada',
            message: 'Ahora puede generar los documentos.',
            content: `La liquidación ha sido guardada correctamente.`,
            confirmText: 'Aceptar',
            type: 'success'
          }
        });

        dialogRef.afterClosed().subscribe(result => {
          // Acción después de cerrar el diálogo si es necesario
        });
      },
      error: (error) => {
        console.error('Error al guardar liquidación:', error);
        this.liquidationSaved = false;
        this.isLoading = false;
        this.cdr.detectChanges();
        alert('❌ Error al guardar la liquidación. Por favor, intenta nuevamente.');
      }
    });
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

  initEditableAuthData(): void {
    if (this.authData) {
      this.editedAuthData = JSON.parse(JSON.stringify(this.authData));
    }
  }

  enableEditMode(): void {
    this.editMode = true;
    this.initEditableAuthData();
  }

  onCellDoubleClick(rowIndex: number, field: string): void {
    if (!this.editMode) return;
    this.editingCell = { rowIndex, field };
  }

  updateCellValue(rowIndex: number, field: string, event: any): void {
    const value = event.target.textContent.trim();
    const row = this.editedAuthData.processedData[rowIndex];

    switch(field) {
      case 'time_worked':
        // Validar formato HH:MM
        if (/^\d{1,2}:\d{2}$/.test(value)) {
          row.time_worked = value;

          // Si había '-' y ahora hay horas, establecer días trabajados en 1
          if (row.days_worked === '-' || row.days_worked === 0) {
            row.days_worked = 1;
          }

          // Si se estableció '00:00' o '-', resetear días trabajados
          if (value === '00:00' || value === '-') {
            row.days_worked = '-';
          }

          this.recalculateRow(rowIndex);
        } else {
          // Restaurar valor anterior si formato inválido
          event.target.textContent = row.time_worked;
          alert('Formato inválido. Use HH:MM (ejemplo: 08:30)');
        }
        break;

      case 'fuel_consumption':
        const fuelValue = parseFloat(value);
        if (!isNaN(fuelValue) && fuelValue >= 0) {
          row.fuel_consumption = fuelValue;
        } else {
          event.target.textContent = row.fuel_consumption;
          alert('Ingrese un número válido');
        }
        break;
    }

    this.hasUnsavedChanges = true;
    this.liquidationSaved = false;
    this.recalculateTotals();
  }

  recalculateRow(rowIndex: number): void {
  const row = this.editedAuthData.processedData[rowIndex];

  // Convertir time_worked a horas equivalentes
  const timeMatch = row.time_worked.match(/(\d+):(\d+)/);
  if (timeMatch) {
    const hours = parseInt(timeMatch[1]);
    const minutes = parseInt(timeMatch[2]);
    // FORZAR 2 decimales usando toFixed y parseFloat
    row.equivalent_hours = parseFloat((hours + (minutes / 60)).toFixed(2));
  }

  // Recalcular monto total con 2 decimales fijos
  row.total_amount = parseFloat((row.equivalent_hours * row.cost_per_hour).toFixed(2));
}

  recalculateTotals(): void {
    let totalSeconds = 0;
    let totalFuelConsumption = 0;
    let totalDaysWorked = 0;

    this.editedAuthData.processedData.forEach((row: any) => {
      const hasValidWork = row.time_worked !== '-' && row.time_worked !== '00:00';

      if (hasValidWork) {
        const timeMatch = row.time_worked.match(/(\d+):(\d+)/);
        if (timeMatch) {
          const hours = parseInt(timeMatch[1]);
          const minutes = parseInt(timeMatch[2]);
          totalSeconds += (hours * 3600) + (minutes * 60);
        }

        totalFuelConsumption += parseFloat(row.fuel_consumption) || 0;

        if (row.days_worked === 1 || row.days_worked === '1') {
          totalDaysWorked += 1;
        }
      }
    });

    const totalHours = Math.floor(totalSeconds / 3600);
    const totalMinutes = Math.floor((totalSeconds % 3600) / 60);
    const totalEquivalentHours = parseFloat((totalHours + (totalMinutes / 60)).toFixed(2));

    const costPerHour = parseFloat(this.editedAuthData.totals.cost_per_hour) || 0;

    // ✅ CÁLCULO CORRECTO: total_amount = cost_per_hour × equivalent_hours
    const totalAmount = parseFloat((totalEquivalentHours * costPerHour).toFixed(2));

    this.editedAuthData.totals = {
      time_worked: `${String(totalHours).padStart(2, '0')}:${String(totalMinutes).padStart(2, '0')}`,
      equivalent_hours: parseFloat(totalEquivalentHours.toFixed(2)),
      fuel_consumption: parseFloat(totalFuelConsumption.toFixed(2)),
      days_worked: totalDaysWorked,
      cost_per_hour: parseFloat(costPerHour.toFixed(2)),
      total_amount: totalAmount  // ✅ Ahora se calcula directamente
    };

    this.updateLiquidationData();
  }

  updateLiquidationData(): void {
  const authData = this.editMode ? this.editedAuthData : this.authData;

  if (!authData || !this.liquidationData) return;

  // FORZAR 2 decimales en cost_per_day
  const costPerDay = authData.totals.days_worked > 0
    ? authData.totals.total_amount / authData.totals.days_worked
    : 0;

  const totalInWords = this.getTotalInWords();

  this.liquidationData = {
    cost_per_day: parseFloat(costPerDay.toFixed(2)),
    total_in_words: totalInWords
  };
}

  addRowTop(): void {
  if (!this.editMode) return;

  const firstRow = this.editedAuthData.processedData[0];
  const firstDate = this.parseDate(firstRow.date);

  const newDate = new Date(firstDate);
  newDate.setDate(newDate.getDate() - 1);

  // ✅ SOLUCIÓN: Convertir cost_per_hour a número primero
  const costPerHour = parseFloat(this.editedAuthData.totals.cost_per_hour) || 0;

  const newRow = {
    date: newDate.toLocaleDateString('es-PE'),
    time_worked: '-',
    equivalent_hours: 0.00,
    fuel_consumption: 0.00,
    days_worked: '-',
    cost_per_hour: parseFloat(costPerHour.toFixed(2)), // ✅ Ahora sí es seguro
    total_amount: 0.00,
    has_work: true,
    isNew: true
  };

  this.editedAuthData.processedData.unshift(newRow);
  this.editedAuthData.minDate = newDate.toLocaleDateString('es-PE');
  this.requestData.minDate = this.formatDateForRequest(newDate);
  this.hasUnsavedChanges = true;
  this.liquidationSaved = false;
  this.updateLiquidationData();
}

  addRowBottom(): void {
  if (!this.editMode) return;

  const lastRow = this.editedAuthData.processedData[this.editedAuthData.processedData.length - 1];
  const lastDate = this.parseDate(lastRow.date);

  const newDate = new Date(lastDate);
  newDate.setDate(newDate.getDate() + 1);

  // ✅ SOLUCIÓN: Convertir cost_per_hour a número primero
  const costPerHour = parseFloat(this.editedAuthData.totals.cost_per_hour) || 0;

  const newRow = {
    date: newDate.toLocaleDateString('es-PE'),
    time_worked: '-',
    equivalent_hours: 0.00,
    fuel_consumption: 0.00,
    days_worked: '-',
    cost_per_hour: parseFloat(costPerHour.toFixed(2)), // ✅ Ahora sí es seguro
    total_amount: 0.00,
    has_work: true,
    isNew: true
  };

  this.editedAuthData.processedData.push(newRow);
  this.editedAuthData.maxDate = newDate.toLocaleDateString('es-PE');
  this.requestData.maxDate = this.formatDateForRequest(newDate);
  this.hasUnsavedChanges = true;
  this.liquidationSaved = false;
  this.updateLiquidationData();
}

  private formatDateForRequest(date: Date): string {
    // El backend espera formato 'YYYY-MM-DD'
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  private parseDate(dateString: string): Date {
    // Asumiendo formato dd/MM/yyyy (ejemplo: "26/11/2024")
    const parts = dateString.split('/');
    if (parts.length === 3) {
      const day = parseInt(parts[0], 10);
      const month = parseInt(parts[1], 10) - 1; // Meses en JS son 0-indexed
      const year = parseInt(parts[2], 10);
      return new Date(year, month, day);
    }

    // Si el formato es diferente, ajusta según corresponda
    return new Date(dateString);
  }

  deleteRow(index: number): void {
  if (!this.editMode) return;

  const totalRows = this.editedAuthData.processedData.length;

  if (index === 0 || index === totalRows - 1) {
    const dialogRef = this.dialog.open(AlertConfirm, {
      width: '450px',
      data: {
        title: '¿Está seguro de eliminar esta fila?',
        message: 'Recordar que esta acción no se puede deshacer',
        content: `Esta acción eliminará permanentemente la fila seleccionada. ¿Desea continuar?`,
        confirmText: 'Confirmar',
        cancelText: 'Cancelar',
        type: 'danger'
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      setTimeout(() => {
          this.editedAuthData.processedData.splice(index, 1);
          this.updateDateRanges();
          this.recalculateTotals();
          this.hasUnsavedChanges = true;
          this.liquidationSaved = false;
          this.cdr.detectChanges();
        }, 0);
    });
  } else {
    const dialogRef = this.dialog.open(AlertConfirm, {
      width: '450px',
      data: {
        title: '¿Está seguro de reiniciar esta fila?',
        message: 'Recordar que esta acción no se puede deshacer',
        content: `Esta acción reiniciara permanentemente la fila seleccionada. ¿Desea continuar?`,
        confirmText: 'Confirmar',
        cancelText: 'Cancelar',
        type: 'danger'
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      setTimeout(() => {
          const row = this.editedAuthData.processedData[index];
          row.time_worked = '-';
          row.equivalent_hours = 0.00;
          row.fuel_consumption = 0.00;
          row.total_amount = 0.00;
          row.days_worked = '-';
          row.has_work = true;

          this.recalculateTotals();
          this.hasUnsavedChanges = true;
          this.liquidationSaved = false;
          this.cdr.detectChanges();
        }, 0);
    });
  }
}

  private updateDateRanges(): void {
    if (this.editedAuthData.processedData.length === 0) return;

    // Obtener primera y última fecha del array
    const firstRow = this.editedAuthData.processedData[0];
    const lastRow = this.editedAuthData.processedData[this.editedAuthData.processedData.length - 1];

    const firstDate = this.parseDate(firstRow.date);
    const lastDate = this.parseDate(lastRow.date);

    // Actualizar en editedAuthData (formato dd/MM/yyyy)
    this.editedAuthData.minDate = firstRow.date;
    this.editedAuthData.maxDate = lastRow.date;

    // Actualizar en requestData (formato YYYY-MM-DD)
    this.requestData.minDate = this.formatDateForRequest(firstDate);
    this.requestData.maxDate = this.formatDateForRequest(lastDate);
  }

  getCostPerDay(): number {
  const authData = this.editMode ? this.editedAuthData : this.authData;
  if (!authData || !authData.totals.days_worked || authData.totals.days_worked === 0) {
    return 0.00;
  }
  const costPerDay = authData.totals.total_amount / authData.totals.days_worked;
  return parseFloat(costPerDay.toFixed(2)); // FORZAR 2 decimales
}

  getTotalInWords(): string {
    const authData = this.editMode ? this.editedAuthData : this.authData;
    if (!authData) {
      return '';
    }

    const totalAmount = authData.totals.total_amount;
    const integerPart = Math.floor(totalAmount);
    const cents = Math.round((totalAmount - integerPart) * 100);

    // Convertir el número entero a palabras
    const words = this.numberToWords(integerPart);

    return `${words} CON ${cents.toString().padStart(2, '0')}/100 SOLES`;
  }

  private numberToWords(num: number): string {
    if (num === 0) return 'CERO';

    const unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    const especiales = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
    const decenas = ['', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    const centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

    const convertirGrupo = (n: number): string => {
      if (n === 0) return '';
      if (n === 100) return 'CIEN';

      let resultado = '';

      // Centenas
      const c = Math.floor(n / 100);
      if (c > 0) {
        resultado += centenas[c];
        n %= 100;
        if (n > 0) resultado += ' ';
      }

      // Decenas y unidades
      if (n >= 10 && n < 20) {
        resultado += especiales[n - 10];
      } else {
        const d = Math.floor(n / 10);
        const u = n % 10;

        if (d > 0) {
          resultado += decenas[d];
          if (u > 0) {
            resultado += (d === 2 ? '' : ' Y ') + unidades[u];
          }
        } else if (u > 0) {
          resultado += unidades[u];
        }
      }

      return resultado;
    };

    if (num < 1000) {
      return convertirGrupo(num);
    } else if (num < 1000000) {
      const miles = Math.floor(num / 1000);
      const resto = num % 1000;
      let resultado = '';

      if (miles === 1) {
        resultado = 'MIL';
      } else {
        resultado = convertirGrupo(miles) + ' MIL';
      }

      if (resto > 0) {
        resultado += ' ' + convertirGrupo(resto);
      }

      return resultado;
    } else {
      const millones = Math.floor(num / 1000000);
      const resto = num % 1000000;
      let resultado = '';

      if (millones === 1) {
        resultado = 'UN MILLÓN';
      } else {
        resultado = convertirGrupo(millones) + ' MILLONES';
      }

      if (resto > 0) {
        if (resto >= 1000) {
          const miles = Math.floor(resto / 1000);
          const restoMiles = resto % 1000;
          if (miles === 1) {
            resultado += ' MIL';
          } else {
            resultado += ' ' + convertirGrupo(miles) + ' MIL';
          }
          if (restoMiles > 0) {
            resultado += ' ' + convertirGrupo(restoMiles);
          }
        } else {
          resultado += ' ' + convertirGrupo(resto);
        }
      }

      return resultado;
    }
  }

  saveAuthChanges(): Observable<any> {
    const authDataToSend = this.editMode && this.editedAuthData 
      ? this.editedAuthData 
      : this.authData;

    const changesData = {
      serviceId: this.reportId,
      adjustmentId: this.selectedAdjustmentId,
      equipment: this.equipmentData,
      request: this.requestData,
      auth: authDataToSend,
      liquidation: this.liquidationData
    };

    this.isLoading = true;

    return this.reportsServicesService.saveAuthChanges(changesData).pipe(
      tap((response) => {
        this.authData = JSON.parse(JSON.stringify(this.editedAuthData || this.authData));
        
        if (response.data && response.data.record) {
          this.requestData.record = response.data.record;
        }

        if (response.data && response.data.adjustment_id) {
          this.selectedAdjustmentId = response.data.adjustment_id;
        }
        
        this.editMode = false;
        this.editedAuthData = null;
        this.hasUnsavedChanges = false;
        this.editingCell = null;
        this.isLoading = false;
        if (!this.isGeneratingDocs) {
          this.liquidationSaved = true;
        }
        this.cdr.detectChanges();

        if (!this.isGeneratingDocs) {
          alert('Cambios guardados correctamente');
        }
      }),
      catchError((error) => {
        console.error('Error al guardar cambios:', error);
        this.errorMessage = 'Error al guardar los cambios. Por favor, intenta nuevamente.';
        this.isLoading = false;
        this.liquidationSaved = false;
        this.cdr.detectChanges();
        return throwError(() => error);
      })
    );
  }

  getGenerateButtonTooltip(): string {
    if (this.editMode) {
      return 'Debe salir del modo edición primero';
    }
    if (this.hasUnsavedChanges) {
      return 'Debe aplicar y guardar los cambios antes de generar documentos';
    }
    if (!this.liquidationSaved) {
      return 'Debe guardar la liquidación antes de generar documentos';
    }
    return 'Generar documentos PDF';
  }
}
