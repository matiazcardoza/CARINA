import { Component, Inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { MatDialogModule, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatInputModule } from '@angular/material/input';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';

// Interfaz para órdenes (mantiene estructura original)
interface OrderItem {
  nombre: string;
  monto: number;
}

// Interfaz para planillas (con los 4 campos requeridos)
interface PayrollItem {
  nombres: string;
  apellidos: string;
  cargo: string;
  montoPago: number;
}

interface DialogData {
  isOrder: boolean;
  items: (OrderItem | PayrollItem)[];
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
  // Listas separadas para órdenes y planillas
  orderItems: OrderItem[] = [];
  payrollItems: PayrollItem[] = [];

  // Formularios separados
  newOrder: OrderItem = { nombre: '', monto: 0 };
  newPayroll: PayrollItem = { nombres: '', apellidos: '', cargo: '', montoPago: 0 };

  constructor(
    public dialogRef: MatDialogRef<ReportAddDeductives>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData
  ) {}

  ngOnInit(): void {
    if (this.data.isOrder) {
      this.orderItems = [...(this.data.items as OrderItem[])];
    } else {
      console.log('Payroll items:', this.data.items);
      this.payrollItems = [...(this.data.items as PayrollItem[])];
    }
  }

  get title(): string {
    return this.data.isOrder ? 'Agregar Órdenes' : 'Agregar Planillas';
  }

  get itemLabel(): string {
    return this.data.isOrder ? 'Orden' : 'Planilla';
  }

  // Getter para la lista actual según el tipo
  get items(): (OrderItem | PayrollItem)[] {
    return this.data.isOrder ? this.orderItems : this.payrollItems;
  }

  agregarItem(): void {
    if (this.data.isOrder) {
      // Validación para órdenes
      if (this.newOrder.nombre.trim() && this.newOrder.monto > 0) {
        this.orderItems.push({ ...this.newOrder });
        this.newOrder = { nombre: '', monto: 0 };
      }
    } else {
      // Validación para planillas (todos los campos requeridos)
      if (this.newPayroll.nombres.trim() && 
          this.newPayroll.apellidos.trim() && 
          this.newPayroll.cargo.trim() && 
          this.newPayroll.montoPago > 0) {
        this.payrollItems.push({ ...this.newPayroll });
        this.newPayroll = { nombres: '', apellidos: '', cargo: '', montoPago: 0 };
      }
    }
  }

  eliminarItem(index: number): void {
    if (this.data.isOrder) {
      this.orderItems.splice(index, 1);
    } else {
      this.payrollItems.splice(index, 1);
    }
  }

  getTotalMonto(): number {
    if (this.data.isOrder) {
      return this.orderItems.reduce((sum, item) => sum + item.monto, 0);
    } else {
      return this.payrollItems.reduce((sum, item) => sum + item.montoPago, 0);
    }
  }

  // Validación para habilitar el botón agregar
  isFormValid(): boolean {
    if (this.data.isOrder) {
      return this.newOrder.nombre.trim() !== '' && this.newOrder.monto > 0;
    } else {
      return this.newPayroll.nombres.trim() !== '' && 
             this.newPayroll.apellidos.trim() !== '' && 
             this.newPayroll.cargo.trim() !== '' && 
             this.newPayroll.montoPago > 0;
    }
  }

  onCancel(): void {
    this.dialogRef.close();
  }

  onSave(): void {
    const result = {
      items: this.data.isOrder ? this.orderItems : this.payrollItems,
      total: this.getTotalMonto(),
      isOrder: this.data.isOrder
    };
    this.dialogRef.close(result);
  }
}