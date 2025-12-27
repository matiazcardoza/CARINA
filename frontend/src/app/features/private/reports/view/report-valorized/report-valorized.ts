import { Component, Inject, OnInit, ChangeDetectorRef } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { MatDialog } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { ReportsServicesService } from '../../../../../services/ReportsServicesService/reports-services-service';
import { ReportAddDeductives } from '../../form/report-add-deductives/report-add-deductives';
import { MatMenuModule } from '@angular/material/menu';

export interface ValorationElement {
  goal: any | null;
  machinery: any | null;
  valoration_amount: number;
}

@Component({
  selector: 'app-report-valorized',
  standalone: true,
  imports: [
    CommonModule,
    MatButtonModule,
    MatIconModule,
    FormsModule,
    MatMenuModule
  ],
  templateUrl: './report-valorized.html',
  styleUrl: './report-valorized.css'
})
export class ReportValorized implements OnInit {

  goalData: any = null;
  machinery: any[] = [];
  valorationAmount: number = 0;
  editedValorationAmount: number = 0;
  editedOperators: { [key: number]: string } = {};
  errorMessage: string | null = null;

  amountSheets: number = 0;
  amountOrders: number = 0;

  valorationSaved: boolean = false;
  hasUnsavedChanges: boolean = false;
  isLoading: boolean = false;

  deletedRows: Set<number> = new Set();
  isHistoryLoading: boolean = false;
  adjustmentHistory: any[] = [];
  selectedAdjustmentId: number | null = null;
  error: string | null = null;
  goalId: number = 0;
  record: any = null;

  deductivesOrder: any[] = [];
  deductivesSheet: any[] = [];

  originalLiquidationIds: number[] = [];

  displayedColumns: string[] = [
    'item',
    'machinery',
    'operator',
    'brand',
    'plate',
    'time_worked',
    'cost_per_hour',
    'total_amount',
    'cost_per_day',
    'days_worked'
  ];

  constructor(
    private router: Router,
    private route: ActivatedRoute,
    private reportsServicesService: ReportsServicesService,
    private dialog: MatDialog,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      this.goalId = +params['goalId'];
      this.loadValorizedData();
      this.loadAdjustmentHistory();
    });
  }

  loadValorizedData(): void{
    this.error = null;
    this.isLoading = true;
    const goalId = this.goalId;

    this.reportsServicesService.getValorationData(goalId)
      .subscribe({
        next: (response) => {
          console.log('Valoration data response:', response);
          this.goalData = response.goal;
          this.machinery = response.machinery;
          this.valorationAmount = response.valoration_amount;
          this.editedValorationAmount = this.valorationAmount;

          this.originalLiquidationIds = this.machinery
            .map(m => m.liquidationId)
            .filter(id => id != null);

          this.editedOperators = {};
          this.machinery.forEach((machinery, index) => {
            this.editedOperators[index] = this.getOperatorNames(machinery.equipment);
          });
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

  // Recalcular automaticamente el total final
  onAmountPlanillaChange(): void {
    this.amountSheets = this.deductivesSheet.reduce((sum, item) => sum + item.montoPago, 0);
  
    const totalDeductions = this.amountSheets + this.amountOrders;
    this.editedValorationAmount = Number(
      (this.finalTotal - totalDeductions).toFixed(2)
    );
    this.hasUnsavedChanges = true;
    this.valorationSaved = false;
  }

  get finalTotal(): number {
    return Number((this.valorationAmount - this.getTotalSheets() - this.amountOrders).toFixed(2));
  }

  deleteRow(index: number): void {
    const confirmar = confirm('¿Está seguro de eliminar esta fila? Esto actualizará el monto total.');
    if (confirmar) {
      this.deletedRows.add(index);
      this.recalculateTotal();
      this.hasUnsavedChanges = true;
      this.valorationSaved = false;
    }
  }

  recalculateTotal(): void {
    let newTotal = 0;
    this.machinery.forEach((machinery, index) => {
      if (!this.deletedRows.has(index)) {
        newTotal += machinery.total_amount;
      }
    });

    this.valorationAmount = Number(newTotal.toFixed(2));
    this.onAmountPlanillaChange();
  }

  generateValorization(): void {

    if (!this.valorationSaved) {
      alert('Debe guardar la valorización antes de generar el documento.');
      return;
    }

    if (this.hasUnsavedChanges) {
      alert('Debe guardar los cambios antes de generar el documento.');
      return;
    }
    const valorationDataSend = {
      record: this.record,
      goal: this.goalData,
      machinery: JSON.parse(JSON.stringify(this.machinery)),
      valoration_amount: this.valorationAmount
    };

    valorationDataSend.machinery = valorationDataSend.machinery.filter(
      (_: any, index: number) => !this.deletedRows.has(index)
    );

    // Aplicar operadores editados
    valorationDataSend.machinery.forEach((machinery: any, index: number) => {
      const originalIndex = this.getOriginalIndex(index);
      if (this.editedOperators[originalIndex] && machinery.equipment?.operators) {
        const operatorNames = this.editedOperators[originalIndex]
          .split(',')
          .map((name: string) => name.trim());

        machinery.equipment.operators = operatorNames.map((name: string, i: number) => ({
          ...(machinery.equipment.operators[i] || {}),
          name: name
        }));
      }
    });

    valorationDataSend.valoration_amount = Number(this.valorationAmount.toFixed(2));

    const finalData = {
      ...valorationDataSend,
      amountOrders: Number(this.amountOrders.toFixed(2)),
      monthlySummary: this.getMonthlySheetSummary(),
      amountFinal: Number(this.finalTotal.toFixed(2))
    };

    this.reportsServicesService.generateValorization(finalData).subscribe({
      next: (response: Blob) => {
        const fileURL = URL.createObjectURL(response);
        window.open(fileURL, '_blank');
        if(this.deductivesSheet.length > 0){
          this.generateDeductives();
        }
      },
      error: () => {
        this.errorMessage = 'Error al generar el PDF. Por favor, intenta nuevamente.';
      }
    });
  }

  generateDeductives(): void {
    const deductivesData = {
      deductivesOrder: this.deductivesOrder,
      deductivesSheet: this.deductivesSheet,
      goalId: this.goalId,
      record: this.record
    };
    console.log('deductivesData', deductivesData);
    this.reportsServicesService.generateDeductives(deductivesData).subscribe({
      next: (response: Blob) => {
        const fileURL = URL.createObjectURL(response);
        window.open(fileURL, '_blank');
      },
      error: () => {
        this.errorMessage = 'Error al generar el PDF. Por favor, intenta nuevamente.';
      }
    });
  }

  getOriginalIndex(filteredIndex: number): number {
    let count = 0;
    for (let i = 0; i < this.machinery.length; i++) {
      if (!this.deletedRows.has(i)) {
        if (count === filteredIndex) return i;
        count++;
      }
    }
    return filteredIndex;
  }

  closeDialog(): void {
    this.router.navigate(['/reports']);
  }

  getOperatorNames(equipment: any): string {
    if (!equipment?.operators || equipment.operators.length === 0) {
      return 'N/A';
    }
    return equipment.operators.map((op: any) => op.name).join(', ');
  }

  getMachineryName(equipment: any): string {
    if (!equipment) return 'N/A';
    return `${equipment.machinery_equipment || ''} ${equipment.model || ''}`.trim() || 'N/A';
  }

  getBrand(equipment: any): string {
    return equipment?.brand || 'N/A';
  }

  getPlate(equipment: any): string {
    return equipment?.plate || 'N/A';
  }

  saveValoration(): void {
    if (!this.goalData) {
      alert('No hay datos para guardar. Espera a que se carguen los datos.');
      return;
    }

    if (this.selectedAdjustmentId !== null && this.valorationSaved) {
      const confirmar = confirm('¿Desea actualizar el registro seleccionado del historial?');
      if (!confirmar) return;
    }

    if (this.valorationSaved && this.selectedAdjustmentId === null) {
      const confirmar = confirm('La valorización ya está guardada. ¿Desea crear un nuevo registro?');
      if (!confirmar) return;
    }

    

    const valorationDataToSend = {
      goal: this.goalData,
      machinery: JSON.parse(JSON.stringify(this.machinery)),
      valoration_amount: this.valorationAmount,
      valoration_amount_final: this.finalTotal
    };

    valorationDataToSend.machinery = valorationDataToSend.machinery.filter(
      (_: any, index: number) => !this.deletedRows.has(index)
    );

    valorationDataToSend.machinery.forEach((machinery: any, index: number) => {
      if (this.editedOperators[index] && machinery.equipment?.operators) {
        const operatorNames = this.editedOperators[index]
          .split(',')
          .map((name: string) => name.trim());

        machinery.equipment.operators = operatorNames.map((name: string, i: number) => ({
          ...(machinery.equipment.operators[i] || {}),
          name: name
        }));
      }
    });

    const activeLiquidationIds = this.machinery
      .map((m, index) => !this.deletedRows.has(index) ? m.liquidationId : null)
      .filter(id => id != null);

    const changesData = {
      goalId: this.goalId,
      adjustmentId: this.selectedAdjustmentId,
      valorationData: valorationDataToSend,
      deductives: {
        deductive_order: this.deductivesOrder,
        amountOrders: Number(this.amountOrders.toFixed(2)),
        deductive_sheet: this.deductivesSheet,
        monthlySummary: this.getMonthlySheetSummary()
      },
      editedValorationAmount: Number(this.editedValorationAmount.toFixed(2)),
      finalAmount: Number(this.editedValorationAmount.toFixed(2)),
      editedOperators: this.editedOperators,
      deletedRows: Array.from(this.deletedRows),
      liquidationIds: activeLiquidationIds,
      originalLiquidationIds: this.originalLiquidationIds
    };

    this.isLoading = true;

    this.reportsServicesService.saveValorization(changesData).subscribe({
      next: (response) => {
        console.log('Valoration saved response:', response);
        if (response.data?.adjustment_id) {
          this.selectedAdjustmentId = response.data.adjustment_id;
        }
        if (response.data?.record) {
          this.record = response.data.record;
        }
        this.valorationSaved = true;
        this.hasUnsavedChanges = false;
        this.isLoading = false;
        this.loadAdjustmentHistory();
        this.cdr.detectChanges();
        alert('✅ Valorización guardada correctamente.');
      },
      error: (error) => {
        console.error('Error al guardar valorización:', error);
        this.valorationSaved = false;
        this.isLoading = false;
        alert('❌ Error al guardar la valorización. Por favor, intenta nuevamente.');
      }
    });
  }

  openDeductivesDialog(isOrder: boolean = false) {
    console.log('este es el deductivesOrder', this.deductivesOrder);
    console.log('este es el deductivesSheet', this.deductivesSheet);
    const dialogRef = this.dialog.open(ReportAddDeductives, {
      width: '1500px',
      maxWidth: '95vw',
      data: {
        isOrder: isOrder,
        items: isOrder ? this.deductivesOrder : this.deductivesSheet,
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        console.log('Datos guardados:', result);
        setTimeout(() => {
          if (result.isOrder) {
            this.deductivesOrder = result.items;
            this.amountOrders = result.total;
            console.log('Órdenes agregadas:', this.deductivesOrder);
          } else {
            this.deductivesSheet = result.items;
            console.log('Planillas agregadas:', this.deductivesSheet);
            this.amountSheets = result.total;
          }

          this.onAmountPlanillaChange();
          this.cdr.detectChanges();
        }, 0);
      }
    });
  }

  loadAdjustmentHistory(): void {
    const goalId = this.goalId;
    if (!this.goalId) {
      console.warn('No goal_id available yet');
      return;
    }
    this.isHistoryLoading = true;

    this.reportsServicesService.getAdjustedValorationData(goalId)
      .subscribe({
        next: (history) => {
          console.log('Adjustment history loaded:', history);
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

  getTotalSheets(): number {
    return this.deductivesSheet.reduce((sum, item) => sum + item.montoPago, 0);
  }

  loadAdjustmentData(adjustment: any): void {
    if (this.hasUnsavedChanges) {
      const confirmar = confirm(
        'Tienes cambios sin guardar. ¿Deseas descartarlos y cargar este registro?'
      );
      if (!confirmar) return;
    }

    this.selectedAdjustmentId = adjustment.id;
    const adjustedData = adjustment.adjusted_data;

    this.record = adjustedData.record;
    this.goalData = adjustedData.goal;
    this.machinery = adjustedData.machinery;
    this.valorationAmount = adjustedData.valoration_amount;
    this.editedValorationAmount = adjustedData.editedValorationAmount || adjustedData.valoration_amount;
    //this.amountSheets = adjustedData.deductives?.deductive_sheet?.amount_sheets || 0;
    this.amountOrders = adjustedData.deductives?.deductive_order?.amount_orders || 0;

    this.deductivesOrder = adjustedData.deductives?.deductive_order?.deductive_order || [];
    this.deductivesSheet = adjustedData.deductives?.deductive_sheet?.deductive_sheet || [];

    if (adjustedData.valoration_amount_final !== undefined && adjustedData.valoration_amount_final !== null) {
      this.editedValorationAmount = adjustedData.valoration_amount_final;
    } else {
      this.editedValorationAmount = Number(
        (this.valorationAmount - this.getTotalSheets() - this.amountOrders).toFixed(2)
      );
    }

    if (adjustedData.editedOperators) {
      this.editedOperators = adjustedData.editedOperators;
    } else {
      this.editedOperators = {};
      this.machinery.forEach((machinery, index) => {
        this.editedOperators[index] = this.getOperatorNames(machinery.equipment);
      });
    }

    if (adjustedData.deletedRows && Array.isArray(adjustedData.deletedRows)) {
      this.deletedRows = new Set(adjustedData.deletedRows);
    } else {
      this.deletedRows = new Set();
    }
    this.valorationSaved = true;
    this.hasUnsavedChanges = false;

    this.cdr.detectChanges();
  }

  loadCurrentVersion(): void {
    if (this.hasUnsavedChanges) {
      const confirmar = confirm(
        'Tienes cambios sin guardar. ¿Deseas descartarlos y cargar la versión actual?'
      );
      if (!confirmar) return;
    }

    this.selectedAdjustmentId = null;
    this.valorationSaved = false;
    this.hasUnsavedChanges = false;

    this.deletedRows.clear();
    //this.amountSheets = 0;
    this.amountOrders = 0;
    this.deductivesOrder = [];
    this.deductivesSheet = [];
    this.loadValorizedData();
  }

  onVersionChange(): void {
    if (this.selectedAdjustmentId === null) {
      this.loadCurrentVersion();
    } else {
      const adjustment = this.adjustmentHistory.find(
        adj => adj.id === this.selectedAdjustmentId
      );
      if (adjustment) {
        this.loadAdjustmentData(adjustment);
      }
    }
  }

  getGenerateButtonTooltip(): string {
    if (!this.valorationSaved) {
      return 'Debe guardar la valorización primero';
    }
    if (this.hasUnsavedChanges) {
      return 'Debe guardar los cambios antes de generar el documento';
    }
    return 'Generar documento PDF de valorización';
  }

  getMonthlySheetSummary(): { mes: number, nombreMes: string, total: number }[] {
    if (!this.deductivesSheet || this.deductivesSheet.length === 0) {
      return [];
    }

    const mesesNombres = [
      'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO',
      'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'
    ];

    // Agrupar por mes
    const monthlyTotals = this.deductivesSheet.reduce((acc, item) => {
      const mes = item.mes;
      if (!acc[mes]) {
        acc[mes] = 0;
      }
      acc[mes] += item.montoPago;
      return acc;
    }, {} as { [key: number]: number });

    // Convertir a array y ordenar por mes
    return Object.keys(monthlyTotals)
      .map(mes => ({
        mes: Number(mes),
        nombreMes: mesesNombres[Number(mes) - 1],
        total: Number(monthlyTotals[Number(mes)].toFixed(2))
      }))
      .sort((a, b) => a.mes - b.mes);
  }
}

