import { Component, Inject } from '@angular/core';
import { MAT_DIALOG_DATA, MatDialogRef, MatDialogModule } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { CommonModule } from '@angular/common';

export interface AlertConfirmData {
  title: string;
  message: string;
  content: string;
  confirmText?: string;
  cancelText?: string;
  type?: 'info' | 'warning' | 'danger' | 'success';
}

@Component({
  selector: 'app-alert-confirm',
  standalone: true,
  imports: [
    CommonModule,
    MatDialogModule,
    MatButtonModule,
    MatIconModule
  ],
  templateUrl: './alert-confirm.html',
  styleUrls: ['./alert-confirm.css']
})
export class AlertConfirm {
  constructor(
    public dialogRef: MatDialogRef<AlertConfirm>,
    @Inject(MAT_DIALOG_DATA) public data: AlertConfirmData
  ) {
    // Establecer valores por defecto
    this.data.confirmText = this.data.confirmText || 'Confirmar';
    this.data.cancelText = this.data.cancelText;
    this.data.type = this.data.type || 'info';
  }

  getIconByType(): string {
    switch (this.data.type) {
      case 'warning':
        return 'warning';
      case 'danger':
        return 'error';
      case 'success':
        return 'check_circle';
      default:
        return 'help_outline';
    }
  }

  getColorByType(): string {
    switch (this.data.type) {
      case 'warning':
        return 'warn';
      case 'danger':
        return 'warn';
      case 'success':
        return 'primary';
      default:
        return 'primary';
    }
  }

  onCancel(): void {
    this.dialogRef.close(false);
  }

  onConfirm(): void {
    this.dialogRef.close(true);
  }
}