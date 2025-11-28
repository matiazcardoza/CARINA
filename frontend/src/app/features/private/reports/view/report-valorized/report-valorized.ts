import { Component, Inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MAT_DIALOG_DATA, MatDialogRef, MatDialogModule } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { ValorationData, ValorationMachinery } from '../../../../../services/DailyWorkLogService/daily-work-log-service';
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
    MatIconModule
  ],
  templateUrl: './report-valorized.html',
  styleUrl: './report-valorized.css'
})
export class ReportValorized implements OnInit {

  valorationData: ValorationData;
  serviceId: number;
  errorMessage: string | null = null;

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
  }

  ngOnInit(): void {
    console.log('Datos de valorización recibidos:', this.valorationData);
  }

  generateValorization(): void {
    const valorationDataSend = this.valorationData;
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
