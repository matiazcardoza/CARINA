import { Component, Inject, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { MatDialogModule, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatInputModule } from '@angular/material/input';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { ReportsServicesService } from '../../../../../services/ReportsServicesService/reports-services-service';

interface OrderDetail {
  idservicio: number;
  ruc : string;
  rsocial : string;
  numero: string;
  precio: number;
}

interface OrderResponse {
  current_page: number;
  data: OrderDetail[];
  total: number;
}

// Interfaz para órdenes (mantiene estructura original)
interface OrderItem {
  idservicio: number;
  ruc: string;
  rsocial: string;
  numero: string;
  precio: number;
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
    ReactiveFormsModule,
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

  orderForm!: FormGroup;
  orderData: OrderDetail[] = [];
  numeroOrdenErrors: string = '';
  isSearching: boolean = false;

  // Formularios separados
  newPayroll: PayrollItem = { nombres: '', apellidos: '', cargo: '', montoPago: 0 };

  constructor(
    private fb: FormBuilder,
    private reportsServicesService: ReportsServicesService,
    private cdr: ChangeDetectorRef,
    public dialogRef: MatDialogRef<ReportAddDeductives>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData
  ) {
    const currentYear = new Date().getFullYear();
    this.orderForm = this.fb.group({
      numeroOrden: ['', [
        Validators.required, 
        Validators.pattern(/^\d{5}$/),
        Validators.minLength(5),
        Validators.maxLength(5)
      ]],
      anio: [currentYear, [Validators.required, Validators.pattern(/^\d{4}$/)]]
    });
  }

  ngOnInit(): void {
    if (this.data.isOrder) {
      this.orderItems = [...(this.data.items as OrderItem[])];
    } else {
      this.payrollItems = [...(this.data.items as PayrollItem[])];
    }
  }

  searchOrder(): void {
    this.numeroOrdenErrors = '';
    
    if (this.orderForm.invalid) {
      this.numeroOrdenErrors = 'Por favor complete todos los campos correctamente.';
      return;
    }

    const numeroOrden = this.orderForm.get('numeroOrden')?.value;
    const anio = this.orderForm.get('anio')?.value;
    
    this.isSearching = true;
    this.cdr.detectChanges(); // Fuerza la detección de cambios

    this.reportsServicesService.getOrderByNumber(numeroOrden, anio).subscribe({
      next: (response: OrderResponse) => {
        this.isSearching = false;
        if (response.data && response.data.length > 0) {
          this.orderData = response.data;
          this.numeroOrdenErrors = '';
        } else {
          this.numeroOrdenErrors = 'No se encontraron datos para esta orden.';
          this.orderData = [];
        }
        this.cdr.detectChanges(); // Fuerza la detección de cambios
      },
      error: (err) => {
        this.isSearching = false;
        console.error('Error al buscar la orden:', err);
        this.numeroOrdenErrors = 'Ocurrió un error al buscar la orden. Intente de nuevo.';
        this.orderData = [];
        this.cdr.detectChanges(); // Fuerza la detección de cambios
      }
    });
  }

  agregarOrdenBuscada(orden: OrderDetail): void {
    const existe = this.orderItems.some(item => item.idservicio === orden.idservicio);
    if (!existe) {
      this.orderItems.push({ ...orden });
      this.orderData = [];
      this.orderForm.reset();
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
    if (!this.data.isOrder) {
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
      return this.orderItems.reduce((sum, item) => sum + item.precio, 0);
    } else {
      return this.payrollItems.reduce((sum, item) => sum + item.montoPago, 0);
    }
  }

  // Validación para habilitar el botón agregar
  isFormValid(): boolean {
    if (this.data.isOrder) {
      return false; // Ya no se usa este método para órdenes
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