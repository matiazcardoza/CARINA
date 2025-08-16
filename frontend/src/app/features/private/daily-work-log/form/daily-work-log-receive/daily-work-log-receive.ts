import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
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
import { DailyWorkLogService } from '../../../../../services/DailyWorkLogService/daily-work-log-service';

interface OrderDetail {
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
    MatProgressSpinnerModule
  ]
})
export class DailyWorkLogReceive implements OnInit {
  orderForm!: FormGroup;
  numeroOrdenErrors: string | null = null;
  isLoading = false;
  orderData: OrderDetail | null = null;
  showResults = false;

  constructor(
    private fb: FormBuilder, 
    private dailyWorkLogService: DailyWorkLogService,
    private cdr: ChangeDetectorRef
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

    this.dailyWorkLogService.getOrderByNumber(numeroOrden).subscribe({
      next: (response: OrderResponse) => {
        console.log('Orden obtenida:', response);
        
        if (response.data && response.data.length > 0) {
          this.orderData = response.data[0]; // Tomar el primer elemento
          this.showResults = true;
          this.numeroOrdenErrors = null;
        } else {
          this.numeroOrdenErrors = 'No se encontraron datos para esta orden.';
          this.orderData = null;
          this.showResults = false;
        }
        
        this.isLoading = false;
        this.cdr.detectChanges(); // Forzar detección de cambios
      },
      error: (err) => {
        console.error('Error al buscar la orden:', err);
        this.numeroOrdenErrors = 'Ocurrió un error al buscar la orden. Intente de nuevo.';
        this.orderData = null;
        this.showResults = false;
        this.isLoading = false;
        this.cdr.detectChanges(); // Forzar detección de cambios
      }
    });
  }

  clearData(): void {
    this.orderForm.reset();
    this.numeroOrdenErrors = null;
    this.orderData = null;
    this.showResults = false;
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
    if (!this.orderData) return 0;
    return this.orderData.cantidad * this.orderData.precio;
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
    if (this.orderData) {
      // Aquí implementarías la lógica para importar la orden
      console.log('Importando orden:', this.orderData);
      // Podrías emitir un evento o navegar a otra página
    }
  }
}