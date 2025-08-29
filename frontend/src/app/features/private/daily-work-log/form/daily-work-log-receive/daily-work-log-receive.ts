import { Component, OnInit, ChangeDetectorRef, Inject } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { MatIconModule } from '@angular/material/icon';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatDividerModule } from '@angular/material/divider';
import { MatChipsModule } from '@angular/material/chips';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { DailyWorkLogService } from '../../../../../services/DailyWorkLogService/daily-work-log-service';
import { TextFieldModule } from '@angular/cdk/text-field';

interface OrderDetail {
  ruc : string;
  rsocial : string;
  telefono1 : string;
  telefono2 : string;
  email : string;
  name_catalog : string;

  anio: string;
  numero: string;
  fecha: string;
  siaf: string;
  detalles_orden: string;
  fecha_prestacion: string;
  plazo_prestacion: number;
  idprocedim: string;
  desprocedim: string;
  prod_proy: string;
  cod_meta: string;
  desmeta: string;
  desrubro: string;
  desuoper: string;
  item: string;
  desmedida: string;
  cantidad: number;
  precio: number;
  detalle: string;
  total_conformidad: number;
  operadores: any[];
  vehiculos: any[];
  state?: number;
}

interface OrderResponse {
  current_page: number;
  data: OrderDetail[];
  total: number;
}

@Component({
  selector: 'app-daily-work-log-receive',
  templateUrl: './daily-work-log-receive.html',
  styleUrls: ['./daily-work-log-receive.css'],
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatIconModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatCardModule,
    MatDividerModule,
    MatChipsModule,
    MatProgressSpinnerModule,
    TextFieldModule
  ]
})
export class DailyWorkLogReceive implements OnInit {
  orderForm!: FormGroup;
  numeroOrdenErrors: string | null = null;
  isLoading = false;
  orderData: OrderDetail | null = null;
  showResults = false;

  totalCalculado: number = 0;

  constructor(
    private fb: FormBuilder, 
    private dailyWorkLogService: DailyWorkLogService,
    private dialogRef: MatDialogRef<DailyWorkLogReceive>,
    private cdr: ChangeDetectorRef,
    @Inject(MAT_DIALOG_DATA) public data: any
  ) {}

  ngOnInit(): void {
    this.orderForm = this.fb.group({
      numeroOrden: ['', [Validators.required, Validators.pattern(/^\d{5}$/)]]
    });
  }

  searchOrder(): void {
    this.numeroOrdenErrors = null;
    this.orderForm.markAllAsTouched();

    if (this.orderForm.invalid) {
      const numeroOrdenControl = this.orderForm.get('numeroOrden');
      if (numeroOrdenControl?.hasError('required')) {
        this.numeroOrdenErrors = 'El número de orden es requerido.';
      } else if (numeroOrdenControl?.hasError('pattern')) {
        this.numeroOrdenErrors = 'El número de orden debe ser un número de 5 dígitos.';
      }
      return;
    }

    const numeroOrden = this.orderForm.get('numeroOrden')?.value;
    this.isLoading = true;
    this.showResults = false;
    this.orderData = null;
    this.totalCalculado = 0;

    this.dailyWorkLogService.getOrderByNumber(numeroOrden).subscribe({
      next: (response: OrderResponse) => {
        if (response.data && response.data.length > 0) {
          this.orderData = response.data[0];
          
          this.showResults = true;
          this.numeroOrdenErrors = null;
        } else {
          this.numeroOrdenErrors = 'No se encontraron datos para esta orden.';
          this.orderData = null;
          this.showResults = false;
          this.totalCalculado = 0;
        }
        this.isLoading = false;
        
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error('Error al buscar la orden:', err);
        this.numeroOrdenErrors = 'Ocurrió un error al buscar la orden. Intente de nuevo.';
        this.orderData = null;
        this.showResults = false;
        this.totalCalculado = 0;
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  clearData(): void {
    this.orderForm.reset();
    this.numeroOrdenErrors = null;
    this.orderData = null;
    this.showResults = false;
    this.totalCalculado = 0;
  }

  hasFieldError(fieldName: string): boolean {
    const field = this.orderForm.get(fieldName);
    return !!(field && field.invalid && (field.dirty || field.touched));
  }

  getFieldError(fieldName: string): string | null {
    const field = this.orderForm.get(fieldName);
    
    if (field && field.errors && (field.dirty || field.touched)) {
      if (field.errors['required']) {
        return 'Este campo es requerido';
      }
      if (field.errors['pattern']) {
        return 'Debe ser un número de 5 dígitos';
      }
    }
    
    return null;
  }

  formatCurrency(amount: number): string {
    return new Intl.NumberFormat('es-PE', {
      style: 'currency',
      currency: 'PEN'
    }).format(amount);
  }

  getTotal(): number {
    return this.totalCalculado;
  }

  formatDate(dateString: string): string {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('es-PE', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    }).format(date);
  }

  importOrder(): void {
    if (!this.orderData) return;
    this.isLoading = true;
    const ImportOrderData = this.orderData;
    
    setTimeout(() => {
      this.dailyWorkLogService.importOrder(ImportOrderData).subscribe({
        next: (response) => {
          this.isLoading = false;
          this.cdr.detectChanges();
          setTimeout(() => {
            this.dialogRef.close(true);
          }, 100);
        },
        error: (error) => {
          console.error('Error al crear:', error);
          this.isLoading = false;
          this.numeroOrdenErrors = 'Error al importar la orden. Intente de nuevo.';
          this.cdr.detectChanges();
        }
      });
    }, 0);
  }

  closeDialog(): void {
    this.dialogRef.close(false);
  }

  getTipoMaquinaria(): string {
    if (!this.orderData?.item) return '';
    
    const texto = this.orderData.item.toUpperCase();
    
    if (texto.includes('MAQUINA SECA')) return 'Máquina Seca';
    if (texto.includes('MAQUINA SERVIDA')) return 'Máquina Servida';
    
    return '';
  }
}