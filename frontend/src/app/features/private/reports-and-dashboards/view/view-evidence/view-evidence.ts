import { Component, Inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatDialogRef, MAT_DIALOG_DATA, MatDialogModule } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatCardModule } from '@angular/material/card';
import { MatGridListModule } from '@angular/material/grid-list';
import { environment } from '../../../../../../environments/environment';
import { DailyWorkLogService } from '../../../../../services/DailyWorkLogService/daily-work-log-service'; // Ajusta la ruta

export interface EvidenceDataElement {
  id: number;
  daily_part_id: number;
  evidence_path: string;
  created_at?: string;
}

export interface DialogData {
  ServiceId: number;
}

@Component({
  selector: 'app-view-evidence',
  standalone: true,
  imports: [
    CommonModule,
    MatDialogModule,
    MatButtonModule,
    MatIconModule,
    MatProgressSpinnerModule,
    MatCardModule,
    MatGridListModule
  ],
  templateUrl: './view-evidence.html',
  styleUrl: './view-evidence.css'
})
export class ViewEvidence implements OnInit {
  evidences: EvidenceDataElement[] = [];
  loading = true;
  error = false;
  selectedImage: string | null = null;

  constructor(
    public dialogRef: MatDialogRef<ViewEvidence>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData,
    private dailyWorkLogService: DailyWorkLogService // Inyecta tu servicio aquí
  ) {}

  ngOnInit(): void {
    this.loadEvidences();
  }

  loadEvidences(): void {
    this.loading = true;
    this.error = false;

    this.dailyWorkLogService.getEvidenceData(this.data.ServiceId).subscribe({
      next: (evidences) => {
        this.evidences = evidences;
        this.loading = false;
      },
      error: (error) => {
        console.error('Error loading evidences:', error);
        this.error = true;
        this.loading = false;
      }
    });
  }

  openImageModal(imagePath: string): void {
    this.selectedImage = imagePath;
  }

  closeImageModal(): void {
    this.selectedImage = null;
  }

  getFullImageUrl(path: string): string {
    // Ajusta esta URL base según tu configuración
    return `${environment.BACKEND_URL}/storage/${path}`;
  }

  onClose(): void {
    this.dialogRef.close();
  }

  onRefresh(): void {
    this.loadEvidences();
  }

  trackByEvidenceId(index: number, evidence: EvidenceDataElement): number {
    return evidence.id;
  }
}
