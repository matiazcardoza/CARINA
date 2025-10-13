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
  state: number; // 0: sin firmas, 1: controlador, 2: residente, 3: supervisor
  evidences: EvidenceDataElement[];
}

export interface GroupedDailyParts {
  date: string;
  parts: DailyPartWithEvidence[];
  totalEvidences: number;
  // Nuevos campos para el estado de firmas
  estadoFirmas: {
    controlador: boolean;
    residente: boolean;
    supervisor: boolean;
  };
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
    MatChipsModule
  ],
  templateUrl: './view-evidence.html',
  styleUrl: './view-evidence.css'
})
export class ViewEvidence implements OnInit {
  groupedDailyParts: GroupedDailyParts[] = [];
  loading = true;
  error = false;
  selectedImage: string | null = null;

  constructor(
    public dialogRef: MatDialogRef<ViewEvidence>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData,
    private dailyWorkLogService: DailyWorkLogService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    this.loadEvidences();
  }

  loadEvidences(): void {
    this.loading = true;
    this.error = false;
    this.cdr.markForCheck();

    this.dailyWorkLogService.getEvidenceData(this.data.ServiceId).subscribe({
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

  private transformToGroupedData(groupedData: any): GroupedDailyParts[] {
    if (!groupedData || typeof groupedData !== 'object') {
      return [];
    }

    const result: GroupedDailyParts[] = [];
    
    Object.keys(groupedData).forEach(date => {
      const parts = groupedData[date];
      
      if (Array.isArray(parts)) {
        const totalEvidences = parts.reduce(
          (sum, part) => sum + (part.evidences?.length || 0), 
          0
        );
        
        // Calcular el estado de firmas basado en el state de todos los parts
        const estadoFirmas = this.calcularEstadoFirmasPorFecha(parts);
        
        result.push({
          date: date,
          parts: parts,
          totalEvidences: totalEvidences,
          estadoFirmas: estadoFirmas
        });
      }
    });
    
    // Ordenar por fecha descendente
    return result.sort((a, b) => 
      new Date(b.date).getTime() - new Date(a.date).getTime()
    );
  }

  /**
   * Calcula el estado de firmas para una fecha específica
   * Si TODOS los parts tienen state >= N, entonces esa firma está completa
   */
  private calcularEstadoFirmasPorFecha(parts: DailyPartWithEvidence[]): {
    controlador: boolean;
    residente: boolean;
    supervisor: boolean;
  } {
    if (!parts || parts.length === 0) {
      return {
        controlador: false,
        residente: false,
        supervisor: false
      };
    }

    // Verificar si TODOS los parts tienen al menos el state requerido
    const todosConControlador = parts.every(part => part.state >= 1);
    const todosConResidente = parts.every(part => part.state >= 2);
    const todosConSupervisor = parts.every(part => part.state >= 3);

    return {
      controlador: todosConControlador,
      residente: todosConResidente,
      supervisor: todosConSupervisor
    };
  }

  /**
   * Obtiene el texto del estado de firmas
   */
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

  /**
   * Obtiene el color del estado de firmas
   */
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
    return `${environment.BACKEND_URL}/storage/${path}`;
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

  getTotalEvidencesCount(): number {
    return this.groupedDailyParts.reduce(
      (total, group) => total + group.totalEvidences, 
      0
    );
  }

  getTotalActivitiesCount(): number {
    return this.groupedDailyParts.reduce(
      (total, group) => total + group.parts.length, 
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