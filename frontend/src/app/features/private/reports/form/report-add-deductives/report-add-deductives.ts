import { Component, Inject, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { MatDialogModule, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatInputModule } from '@angular/material/input';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatSelectModule } from '@angular/material/select';
import { ReportsServicesService } from '../../../../../services/ReportsServicesService/reports-services-service';

interface OrderDetail {
  idservicio?: number;      // Para orden de servicio
  idserviciodet?: number;   // ID único para orden de servicio
  idcompra?: number;        // Para orden de compra
  idcompradet?: number;     // ID único para orden de compra
  ruc: string;
  rsocial: string;
  numero: string;
  precio: number;
  cantidad: number;
  item: string;
}

interface OrderResponse {
  current_page: number;
  data: OrderDetail[];
  total: number;
}

// Interfaz para órdenes (mantiene estructura original)
interface OrderItem {
  idservicio?: number;      // Para orden de servicio
  idserviciodet?: number;   // ID único para orden de servicio
  idcompradet?: number;     // ID único para orden de compra
  ruc: string;
  rsocial: string;
  numero: string;
  monto: number;
  item?: string;
}

// Interfaz para planillas (con los 4 campos requeridos)
interface PayrollItem {
  nombres: string;
  apellidos: string;
  cargo: string;
  mes: number;
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
    MatIconModule,
    MatSelectModule
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

  tipoOrden: 'servicio' | 'compra' = 'servicio';

  // Formularios separados
  newPayroll: PayrollItem = { nombres: '', apellidos: '', cargo: '', mes: new Date().getMonth() + 1, montoPago: 0 };

  meses = [
    { id: 1, nombre: 'Enero' },
    { id: 2, nombre: 'Febrero' },
    { id: 3, nombre: 'Marzo' },
    { id: 4, nombre: 'Abril' },
    { id: 5, nombre: 'Mayo' },
    { id: 6, nombre: 'Junio' },
    { id: 7, nombre: 'Julio' },
    { id: 8, nombre: 'Agosto' },
    { id: 9, nombre: 'Septiembre' },
    { id: 10, nombre: 'Octubre' },
    { id: 11, nombre: 'Noviembre' },
    { id: 12, nombre: 'Diciembre' }
  ];

  constructor(
    private fb: FormBuilder,
    private reportsServicesService: ReportsServicesService,
    private cdr: ChangeDetectorRef,
    public dialogRef: MatDialogRef<ReportAddDeductives>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData
  ) {
    const currentYear = new Date().getFullYear();
    this.orderForm = this.fb.group({
      tipoOrden: ['servicio', Validators.required],
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
    const tipoOrden = this.orderForm.get('tipoOrden')?.value;
    
    this.isSearching = true;
    this.cdr.detectChanges(); // Fuerza la detección de cambios

    this.reportsServicesService.getOrderByNumber(numeroOrden, anio, tipoOrden).subscribe({
      next: (response: OrderResponse) => {
        console.log(response);
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
    const tipoOrden = this.orderForm.get('tipoOrden')?.value;
    const uniqueId = tipoOrden === 'servicio' ? orden.idserviciodet : orden.idcompradet;
    const existe = this.orderItems.some(item => {
      const itemUniqueId = tipoOrden === 'servicio' ? item.idserviciodet : item.idcompradet;
      return itemUniqueId === uniqueId;
    });
    
    if (!existe) {
      const monto = orden.precio * orden.cantidad;
      
      const nuevoItem = { 
        idservicio: tipoOrden === 'servicio' ? orden.idservicio : orden.idcompra,
        idserviciodet: orden.idserviciodet,
        idcompradet: orden.idcompradet,
        ruc: orden.ruc,
        rsocial: orden.rsocial,
        numero: orden.numero,
        monto: monto,
        item: orden.item
      };
      
      this.orderItems.push(nuevoItem);
      const longitudAntes = this.orderData.length;
      this.orderData = this.orderData.filter(item => {
        const itemUniqueId = tipoOrden === 'servicio' ? item.idserviciodet : item.idcompradet;
        return itemUniqueId !== uniqueId;
      });
      
      const longitudDespues = this.orderData.length;
      console.log(`Ítems en orderData: ${longitudAntes} -> ${longitudDespues}`);
      if (this.orderData.length === 0) {
        this.orderForm.reset({
          tipoOrden: 'servicio',
          numeroOrden: '',
          anio: new Date().getFullYear()
        });
      }
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
          this.newPayroll.mes > 0 &&
          this.newPayroll.montoPago > 0) {
        this.payrollItems.push({ ...this.newPayroll });
        this.newPayroll = { nombres: '', apellidos: '', cargo: '', mes: new Date().getMonth() + 1, montoPago: 0 };
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
      return false; // Ya no se usa este método para órdenes
    } else {
      return this.newPayroll.nombres.trim() !== '' && 
             this.newPayroll.apellidos.trim() !== '' && 
             this.newPayroll.cargo.trim() !== '' && 
             this.newPayroll.mes > 0 &&
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