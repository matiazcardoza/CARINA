import { Component, Inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogRef, MatDialogModule } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { ValorationData } from '../../../../../services/DailyWorkLogService/daily-work-log-service';
import { ReportsServicesService } from '../../../../../services/ReportsServicesService/reports-services-service';

export interface DialogData {
  valorationData: ValorationData;
  serviceId: number;
}

@Component({
  selector: 'app-report-valorized',
  standalone: true,
  imports: [
    CommonModule,
    MatDialogModule,
    MatButtonModule,
    MatIconModule,
    FormsModule
  ],
  templateUrl: './report-valorized.html',
  styleUrl: './report-valorized.css'
})
export class ReportValorized implements OnInit {

  valorationData: ValorationData;
  editedValorationAmount: number;
  editedOperators: { [key: number]: string } = {};
  serviceId: number;
  errorMessage: string | null = null;

  amountPlanilla: number = 0;   // NUEVO

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
    @Inject(MAT_DIALOG_DATA) public data: DialogData,
    private dialogRef: MatDialogRef<ReportValorized>,
    private reportsServicesService: ReportsServicesService
  ) {
    this.valorationData = data.valorationData;
    this.serviceId = data.serviceId;
    this.editedValorationAmount = data.valorationData.valoration_amount;

    this.valorationData.machinery.forEach((machinery, index) => {
      this.editedOperators[index] = this.getOperatorNames(machinery.equipment);
    });
  }

  ngOnInit(): void {
    console.log('Datos de valorización recibidos:', this.valorationData);
  }

  // Recalcular automaticamente el total final
  onAmountPlanillaChange(): void {
    if (this.amountPlanilla >= 0) {
      this.editedValorationAmount =
        this.valorationData.valoration_amount - this.amountPlanilla;
    }
  }

  generateValorization(): void {
    const valorationDataSend = JSON.parse(JSON.stringify(this.valorationData));

    // Aplicar operadores editados
    valorationDataSend.machinery.forEach((machinery: any, index: number) => {
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

    // Enviar con EXACTAMENTE 2 DECIMALES
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

  closeDialog(): void {
    this.dialogRef.close();
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
}

