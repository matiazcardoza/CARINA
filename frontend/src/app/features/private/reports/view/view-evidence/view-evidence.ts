import { Component, Inject, OnInit, ChangeDetectionStrategy, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatDialogRef, MAT_DIALOG_DATA, MatDialogModule } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatCardModule } from '@angular/material/card';
import { MatGridListModule } from '@angular/material/grid-list';
import { MatExpansionModule } from '@angular/material/expansion';
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
  evidences: EvidenceDataElement[];
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
    MatExpansionModule
  ],
  templateUrl: './view-evidence.html',
  styleUrl: './view-evidence.css'
})
export class ViewEvidence implements OnInit {
  dailyParts: DailyPartWithEvidence[] = [];
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
        // Procesamos la respuesta del backend que tiene la estructura de tu función PHP
        if (response && response.data) {
          this.dailyParts = response.data;
        } else {
          // Si la respuesta no tiene esa estructura, asumimos que es directamente el array
          this.dailyParts = response;
        }
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

  // Método para contar el total de evidencias
  getTotalEvidencesCount(): number {
    return this.dailyParts.reduce((total, part) => total + part.evidences.length, 0);
  }

  // Método para verificar si hay evidencias en total
  hasAnyEvidence(): boolean {
    return this.dailyParts.some(part => part.evidences.length > 0);
  }
}
