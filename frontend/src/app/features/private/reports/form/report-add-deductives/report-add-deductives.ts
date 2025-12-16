import { Component, Inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { MatDialogModule, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatInputModule } from '@angular/material/input';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';

interface DeductiveItem {
  nombre: string;
  monto: number;
}

interface DialogData {
  isOrder: boolean;
}

@Component({
  selector: 'app-report-add-deductives',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    MatDialogModule,
    MatButtonModule,
    MatInputModule,
    MatFormFieldModule,
    MatIconModule
  ],
  templateUrl: './report-add-deductives.html',
  styleUrl: './report-add-deductives.css'
})
export class ReportAddDeductives {
  items: DeductiveItem[] = [];
  newItem: DeductiveItem = { nombre: '', monto: 0 };

  constructor(
    public dialogRef: MatDialogRef<ReportAddDeductives>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData
  ) {}

  get title(): string {
    return this.data.isOrder ? 'Agregar Órdenes' : 'Agregar Planillas';
  }

  get itemLabel(): string {
    return this.data.isOrder ? 'Orden' : 'Planilla';
  }

  agregarItem(): void {
    if (this.newItem.nombre.trim() && this.newItem.monto > 0) {
      this.items.push({ ...this.newItem });
      this.newItem = { nombre: '', monto: 0 };
    }
  }

  eliminarItem(index: number): void {
    this.items.splice(index, 1);
  }

  getTotalMonto(): number {
    return this.items.reduce((sum, item) => sum + item.monto, 0);
  }

  onCancel(): void {
    this.dialogRef.close();
  }

  onSave(): void {
    if (this.items.length > 0) {
      this.dialogRef.close({
        items: this.items,
        total: this.getTotalMonto(),
        isOrder: this.data.isOrder
      });
    }
  }
}