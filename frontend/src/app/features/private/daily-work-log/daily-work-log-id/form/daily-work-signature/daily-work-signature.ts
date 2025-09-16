import { Component, Inject } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatToolbarModule } from '@angular/material/toolbar';
import { CommonModule } from '@angular/common';

interface DialogData {
  workLogId: number;
  date: string;
}

@Component({
  selector: 'app-daily-work-signature',
  standalone: true,
  imports: [
    CommonModule,
    MatIconModule,
    MatButtonModule,
    MatToolbarModule
  ],
  templateUrl: './daily-work-signature.html',
  styleUrl: './daily-work-signature.css'
})
export class DailyWorkSignature {
  
  constructor(
    public dialogRef: MatDialogRef<DailyWorkSignature>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData
  ) {}

  onCancel(): void {
    this.dialogRef.close(false);
  }

  onSave(): void {
    console.log('Guardando firma para WorkLog ID:', this.data.workLogId);
    this.dialogRef.close(true);
  }

  onNoClick(): void {
    this.dialogRef.close(false);
  }
}