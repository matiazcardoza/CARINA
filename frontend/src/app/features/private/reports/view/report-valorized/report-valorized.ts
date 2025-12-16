import { Component, Inject, OnInit, ChangeDetectorRef } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { MatDialog } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { ValorationData } from '../../../../../services/DailyWorkLogService/daily-work-log-service';
import { ReportsServicesService } from '../../../../../services/ReportsServicesService/reports-services-service';
import { ReportAddDeductives } from '../../form/report-add-deductives/report-add-deductives';

export interface ValorationElement {
  equipment: any | null;
  request: any | null;
  auth: any | null;
  liquidation: any | null;
}

@Component({
  selector: 'app-report-valorized',
  standalone: true,
  imports: [
    CommonModule,
    MatButtonModule,
    MatIconModule,
    FormsModule
  ],
  templateUrl: './report-valorized.html',
  styleUrl: './report-valorized.css'
})
export class ReportValorized implements OnInit {

  valorationData: ValorationData = {} as ValorationData;
  editedValorationAmount: number = 0;
  editedOperators: { [key: number]: string } = {};
  errorMessage: string | null = null;

  amountPlanilla: number = 0;

  valorationSaved: boolean = false;
  isLoading: boolean = false;

  deletedRows: Set<number> = new Set();
  isHistoryLoading: boolean = false;
  adjustmentHistory: any[] = [];
  selectedAdjustmentId: number | null = null;
  error: string | null = null;
  goalId: number = 0;

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
  ) {
    const navigation = this.router.getCurrentNavigation();
    if (navigation?.extras.state) {
      this.valorationData = navigation.extras.state['valorationData'];
      this.editedValorationAmount = this.valorationData.valoration_amount;

      this.valorationData.machinery.forEach((machinery, index) => {
        this.editedOperators[index] = this.getOperatorNames(machinery.equipment);
      });
    }
  }

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      this.goalId = +params['goalId'];
      console.log('Report ID:', this.goalId);
    });
    this.loadValorizedData();
    this.loadAdjustmentHistory();
  }

  loadValorizedData(): void{
    this.error = null;
    this.isLoading = true;
    const goalId = this.goalId;

    this.reportsServicesService.getValorationData(goalId)
      .subscribe({
        next: (response) => {
          console.log('Valoration data response:', response);
          
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
    if (this.amountPlanilla >= 0) {
      this.editedValorationAmount = Number(
        (this.valorationData.valoration_amount - this.amountPlanilla).toFixed(2)
      );
    }
  }

  deleteRow(index: number): void {
    const confirmar = confirm('¿Está seguro de eliminar esta fila? Esto actualizará el monto total.');
    if (confirmar) {
      this.deletedRows.add(index);
      this.recalculateTotal();
    }
  }

  recalculateTotal(): void {
    let newTotal = 0;
    this.valorationData.machinery.forEach((machinery, index) => {
      if (!this.deletedRows.has(index)) {
        newTotal += machinery.total_amount;
      }
    });

    this.valorationData.valoration_amount = Number(newTotal.toFixed(2));
    this.onAmountPlanillaChange();
  }

  generateValorization(): void {
    const valorationDataSend = JSON.parse(JSON.stringify(this.valorationData));

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

    // Enviar con EXACTAMENTE 2 DECIMALES
    valorationDataSend.valoration_amount = Number(this.valorationData.valoration_amount.toFixed(2));
    valorationDataSend.editedValorationAmount = Number(this.editedValorationAmount.toFixed(2));
    valorationDataSend.amountPlanilla = Number(this.amountPlanilla.toFixed(2));

    this.reportsServicesService.generateValorization(valorationDataSend).subscribe({
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
    for (let i = 0; i < this.valorationData.machinery.length; i++) {
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
    if (!this.valorationData) {
      alert('No hay datos para guardar. Espera a que se carguen los datos.');
      return;
    }
    if (this.valorationSaved) {
      const confirmar = confirm('La valorización ya está guardada. ¿Desea actualizarla?');
      if (!confirmar) return;
    }
    const valorationDataToSend = JSON.parse(JSON.stringify(this.valorationData));
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
    const changesData = {
      valorationData: valorationDataToSend,
      editedValorationAmount: Number(this.editedValorationAmount.toFixed(2)),
      amountPlanilla: Number(this.amountPlanilla.toFixed(2)),
      finalAmount: Number((this.editedValorationAmount).toFixed(2))
    };

    this.isLoading = true;

    this.reportsServicesService.saveValorization(changesData).subscribe({
      next: (response) => {
        console.log('Valorización guardada:', response);

        this.valorationSaved = true;
        this.isLoading = false;
        alert('✅ Valorización guardada correctamente. Ahora puede generar el documento PDF.');
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
    const dialogRef = this.dialog.open(ReportAddDeductives, {
      width: '900px',
      data: {
        isOrder: isOrder,
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        console.log('Datos guardados:', result);
        // result contendrá:
        // {
        //   items: DeductiveItem[],
        //   total: number,
        //   isOrder: boolean
        // }
        
        // Aquí puedes procesar los datos según necesites
        if (result.isOrder) {
          console.log('Órdenes agregadas:', result.items);
        } else {
          console.log('Planillas agregadas:', result.items);
        }
      }
    });
  }

  loadAdjustmentHistory(): void {
    this.isHistoryLoading = true;
    const goalId = this.valorationData.goal.goal_id!;
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

  loadAdjustmentData(adjustment: any): void {
    console.log('Loading adjustment data:', adjustment);
    if (this.deletedRows.size > 0 || this.amountPlanilla !== 0) {
      const confirmar = confirm(
        '¿Tienes cambios sin guardar. ¿Deseas descartarlos y cargar este registro?'
      );
      if (!confirmar) return;
    }

    this.selectedAdjustmentId = adjustment.id;
    const adjustedData = adjustment.adjusted_data;
    
    this.valorationData = adjustedData.valorationData;
    this.editedValorationAmount = adjustedData.editedValorationAmount;
    this.amountPlanilla = adjustedData.amountPlanilla;
    
    if (adjustedData.editedOperators) {
      this.editedOperators = adjustedData.editedOperators;
    } else {
      this.editedOperators = {};
      this.valorationData.machinery.forEach((machinery, index) => {
        this.editedOperators[index] = this.getOperatorNames(machinery.equipment);
      });
    }
    
    this.deletedRows = new Set();
    this.valorationSaved = true;
    
    this.cdr.detectChanges();
  }

  loadCurrentVersion(): void {
    if (this.deletedRows.size > 0 || this.amountPlanilla !== 0) {
      const confirmar = confirm(
        'Tienes cambios sin guardar. ¿Deseas descartarlos y cargar la versión actual?'
      );
      if (!confirmar) return;
    }
    
    this.selectedAdjustmentId = null;
    this.valorationSaved = false;
    // CAMBIAR data.valorationData por obtenerlo del state del router
    const navigation = this.router.getCurrentNavigation();
    const originalData = navigation?.extras.state?.['valorationData'];
    
    this.valorationData = JSON.parse(JSON.stringify(originalData || this.valorationData));
    this.editedValorationAmount = this.valorationData.valoration_amount;
    this.amountPlanilla = 0;
    this.deletedRows = new Set();
    this.editedOperators = {};
    this.valorationData.machinery.forEach((machinery, index) => {
      this.editedOperators[index] = this.getOperatorNames(machinery.equipment);
    });
    
    this.cdr.detectChanges();
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
}

