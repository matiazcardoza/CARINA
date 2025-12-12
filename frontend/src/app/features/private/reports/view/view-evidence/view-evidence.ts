import { Component, Inject, OnInit, ChangeDetectionStrategy, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatDialogRef, MAT_DIALOG_DATA, MatDialogModule } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatCardModule } from '@angular/material/card';
import { MatGridListModule } from '@angular/material/grid-list';
import { MatExpansionModule } from '@angular/material/expansion';
import { MatChipsModule } from '@angular/material/chips';
import { environment } from '../../../../../../environments/environment';
import { DailyWorkLogService } from '../../../../../services/DailyWorkLogService/daily-work-log-service';
import { MatTooltipModule } from '@angular/material/tooltip';
import { ReportsServicesService } from '../../../../../services/ReportsServicesService/reports-services-service';
import JSZip from 'jszip';
import { FormsModule } from '@angular/forms';

export interface EvidenceDataElement {
  id: number;
  daily_part_id: number;
  state: number;
  evidence_path: string;
  created_at?: string;
}

export interface DailyPartWithEvidence {
  id: number;
  description: string;
  work_date: string;
  time_worked?: string;
  state: number;
  user_name?: string;
  user_lastname?: string;
  evidences: EvidenceDataElement[];
}

export interface ShiftGroup {
  shift_id: number;
  shift_name: string;
  document_id: number;
  path_document: string;
  state: number;
  items: DailyPartWithEvidence[];
  totalEvidences: number;
  estadoFirmas: {
    controlador: boolean;
    residente: boolean;
    supervisor: boolean;
  };
}
export interface GroupedDailyParts {
  date: string;
  shifts: ShiftGroup[];
  totalEvidences: number;
}

export interface DialogData {
  ServiceId: number;
}

@Component({
  selector: 'app-view-evidence',
  standalone: true,
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    CommonModule,
    MatDialogModule,
    MatButtonModule,
    MatIconModule,
    MatProgressSpinnerModule,
    MatCardModule,
    MatGridListModule,
    MatExpansionModule,
    MatChipsModule,
    MatTooltipModule,
    FormsModule
  ],
  templateUrl: './view-evidence.html',
  styleUrl: './view-evidence.css'
})
export class ViewEvidence implements OnInit {
  groupedDailyParts: GroupedDailyParts[] = [];
  loading = true;
  error = false;
  selectedImage: string | null = null;
  currentFilter: number = 1;

  constructor(
    public dialogRef: MatDialogRef<ViewEvidence>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData,
    private dailyWorkLogService: DailyWorkLogService,
    private reportsServicesService: ReportsServicesService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.loadEvidences();
  }

  loadEvidences(): void {
    this.loading = true;
    this.error = false;
    this.cdr.markForCheck();

    this.dailyWorkLogService.getEvidenceData(this.data.ServiceId, this.currentFilter).subscribe({
      next: (response: any) => {
        console.log('Evidences response:', response);

        const data = response.data || response;
        this.groupedDailyParts = this.transformToGroupedData(data);

        console.log('Grouped daily parts:', this.groupedDailyParts);
        this.loading = false;
        this.cdr.markForCheck();
      },
      error: (error) => {
        console.error('Error loading evidences:', error);
        this.error = true;
        this.loading = false;
        this.cdr.markForCheck();
      }
    });
  }

  onFilterChange(filterValue: number): void {
    if (this.currentFilter !== filterValue) {
      this.currentFilter = filterValue;
      this.loadEvidences();
    }
  }

  onDownloadDailyPart(pathDocument: string): void {
    if (!pathDocument) {
      console.error('No hay documento disponible');
      return;
    }
    const timestamp = new Date().getTime();
    const urlWithTimestamp = `${pathDocument}?t=${timestamp}`;

    this.reportsServicesService.openDocumentInNewTab(urlWithTimestamp);
  }

  private transformToGroupedData(groupedData: any): GroupedDailyParts[] {
    if (!groupedData || typeof groupedData !== 'object') {
      return [];
    }

    const result: GroupedDailyParts[] = [];

    Object.keys(groupedData).forEach(date => {
      const shiftsData = groupedData[date];

      if (Array.isArray(shiftsData)) {
        const shifts: ShiftGroup[] = shiftsData.map(shift => {
          const totalEvidences = shift.items.reduce(
            (sum: number, part: any) => sum + (part.evidences?.length || 0),
            0
          );

          return {
            shift_id: shift.shift_id,
            shift_name: shift.shift_name,
            document_id: shift.document_id,
            path_document: shift.path_document,
            state: shift.state,
            items: shift.items,
            totalEvidences: totalEvidences,
            estadoFirmas: this.calcularEstadoFirmasPorState(shift.state)
          };
        });

        const totalEvidences = shifts.reduce(
          (sum, shift) => sum + shift.totalEvidences,
          0
        );

        result.push({
          date: date,
          shifts: shifts,
          totalEvidences: totalEvidences
        });
      }
    });
    return result.sort((a, b) =>
      new Date(b.date).getTime() - new Date(a.date).getTime()
    );
  }

  private calcularEstadoFirmasPorState(state: number): {
    controlador: boolean;
    residente: boolean;
    supervisor: boolean;
  } {
    return {
      controlador: state >= 1,
      residente: state >= 2,
      supervisor: state >= 3
    };
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

  openImageModal(imagePath: string): void {
    this.selectedImage = imagePath;
    this.cdr.markForCheck();
  }

  closeImageModal(): void {
    this.selectedImage = null;
    this.cdr.markForCheck();
  }

  getFullImageUrl(path: string): string {
    const timestamp = new Date().getTime();
    return `${environment.BACKEND_URL}/storage/${path}?t=${timestamp}`;
  }

  onClose(): void {
    this.dialogRef.close();
  }

  onRefresh(): void {
    this.loadEvidences();
  }

  trackByDailyPartId(index: number, dailyPart: DailyPartWithEvidence): number {
    return dailyPart.id;
  }

  trackByEvidenceId(index: number, evidence: EvidenceDataElement): number {
    return evidence.id;
  }

  trackByShiftId(index: number, shift: ShiftGroup): number {
    return shift.shift_id;
  }

  getTotalEvidencesCount(): number {
    return this.groupedDailyParts.reduce(
      (total, group) => total + group.totalEvidences,
      0
    );
  }

  getTotalActivitiesCount(): number {
    return this.groupedDailyParts.reduce(
      (total, group) => total + group.shifts.reduce(
        (shiftTotal, shift) => shiftTotal + shift.items.length,
        0
      ),
      0
    );
  }

  trackByDate(index: number, group: GroupedDailyParts): string {
    return group.date;
  }

  hasAnyEvidence(): boolean {
    return this.groupedDailyParts.some(group => group.totalEvidences > 0);
  }
}
