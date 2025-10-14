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
import { MatSelectModule } from '@angular/material/select';
import { MatOptionModule } from '@angular/material/core';

interface OrderDetail {
  idservicio: number;
  ruc : string;
  rsocial : string;
  anio: string;
  numero: string;
  siaf: string;
  fecha_prestacion: string;
  plazo_prestacion: number;
  cod_meta: string;
  desmeta: string;
  item: string;
  idmeta: number;
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
    TextFieldModule,
    MatSelectModule,
    MatOptionModule
  ]
})
export class DailyWorkLogReceive implements OnInit {
  orderForm!: FormGroup;
  importForm!: FormGroup;
  numeroOrdenErrors: string | null = null;
  isLoading = false;
  orderData: OrderDetail | null = null;
  showResults = false;

  tipoMaquinariaOptions = [
    { value: 1, label: 'Máquina Seca' },
    { value: 2, label: 'Máquina Servida' }
  ];

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

    this.importForm = this.fb.group({
      operador: ['', Validators.required],
      tipoMaquinaria: ['', Validators.required],
      maquinaria: ['', Validators.required],
      marca: ['', Validators.required],
      modelo: ['', Validators.required],
      capacidad: ['', Validators.required],
      year: ['', Validators.required],
      serie: ['', Validators.required],
      placa: ['', Validators.required],
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

    this.dailyWorkLogService.getOrderByNumber(numeroOrden).subscribe({
      next: (response: OrderResponse) => {
        if (response.data && response.data.length > 0) {
          this.orderData = response.data[0];
          this.showResults = true;
          this.numeroOrdenErrors = null;
          
          this.importForm.patchValue({
            operador: this.getOperator(),
            tipoMaquinaria: this.getTipoMaquinaria(),
            maquinaria: this.getMachineryEquipment(),
            marca: this.getBrand(),
            modelo: this.getModel(),
            capacidad: this.getAbility(),
            year: this.getYear(),
            serie: this.getNumberSerial(),
            placa: this.getPlate(),
          });
        } else {
          this.numeroOrdenErrors = 'No se encontraron datos para esta orden.';
          this.orderData = null;
          this.showResults = false;
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error('Error al buscar la orden:', err);
        this.numeroOrdenErrors = 'Ocurrió un error al buscar la orden. Intente de nuevo.';
        this.orderData = null;
        this.showResults = false;
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  clearData(): void {
    this.orderForm.reset();
    this.importForm.reset();
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

  formatDate(dateString: string): string {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('es-PE', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    }).format(date);
  }

  importOrder(): void {
    this.importForm.markAllAsTouched();
    if (!this.orderData || this.importForm.invalid) {
      return;
    }
    
    this.isLoading = true;
    
    const formData = new FormData();
    const formValue = this.importForm.value;
    
    for (const key in formValue) {
        if (formValue.hasOwnProperty(key) && formValue[key] !== null) {
            formData.append(key, formValue[key]);
        }
    }
    
    if (this.orderData) {
      formData.append('idmeta', this.orderData.idmeta.toString());
      formData.append('ruc', this.orderData.ruc);
      formData.append('rsocial', this.orderData.rsocial);
      formData.append('cod_meta', this.orderData.cod_meta);
      formData.append('desmeta', this.orderData.desmeta);
      formData.append('idservicio', this.orderData.idservicio.toString());
      formData.append('fechaPrestacion', this.orderData.fecha_prestacion);
      formData.append('plazoPrestacion', this.orderData.plazo_prestacion.toString());
      formData.append('description', this.getDescripcion());

      const fechaInicio = new Date(this.orderData.fecha_prestacion);
      const fechaFinal = new Date(fechaInicio);
      fechaFinal.setDate(fechaInicio.getDate() + this.orderData.plazo_prestacion);

      formData.append('fechaFinal', fechaFinal.toISOString().split('T')[0]);
    }

    this.dailyWorkLogService.importOrder(formData).subscribe({
      next: (response) => {
        this.isLoading = false;
        this.cdr.detectChanges();
        console.log(response.message);
        this.dialogRef.close(response);
      },
      error: (error) => {
        console.error('Error al importar:', error);
        this.isLoading = false;
        this.numeroOrdenErrors = 'Error al importar la orden. Intente de nuevo.';
        this.cdr.detectChanges();
      }
    });
  }

  closeDialog(): void {
    this.dialogRef.close(false);
  }

  getOperator(): string {
    if (!this.orderData?.item) return '';
    const texto = this.orderData.item.toUpperCase();
    const match = texto.match(/OPERADOR\s*\d*:\s*([^\n-]+)/);
    return match ? match[1].trim() : '';
  }
  
  getMachineryEquipment(): string {
    if (!this.orderData?.item) return '';
    const texto = this.orderData.item.toUpperCase();
    const match = texto.match(/(?:ALQUILER|SERVICIO)\s+DE\s+([^,]+)/);
    return match ? match[1].trim() : '';
  }

  getTipoMaquinaria(): number | null{
    if (!this.orderData?.item) return null;
    const texto = this.orderData.item.toUpperCase();
    if (texto.includes('MAQUINA SECA')) return 1;
    if (texto.includes('MAQUINA SERVIDA')) return 2;
    return null;
  }

  getAbility(): string {
    if (!this.orderData?.item) return '';
    const texto = this.orderData.item.toUpperCase();
    const match = texto.match(/MOTOR:\s*([^\n-]+).*POTENCIA:\s*([^\n-]+)/s);
    return match ? `${match[1].trim()} - ${match[2].trim()}` : '';
  }

  getBrand(): string {
    if (!this.orderData?.item) return '';
    const texto = this.orderData.item.toUpperCase();
    const match = texto.match(/MARCA:\s*([^\n-]+)/);
    return match ? match[1].trim() : '';
  }

  getModel(): string {
    if (!this.orderData?.item) return '';
    const texto = this.orderData.item.toUpperCase();
    const match = texto.match(/MODELO:\s*([^\n-]+)/);
    return match ? match[1].trim() : '';
  }

  getNumberSerial(): string {
    if (!this.orderData?.item) return '';
    const texto = this.orderData.item.toUpperCase();
    const match = texto.match(/SERIE:\s*([^\n-]+)/);
    return match ? match[1].trim() : '';
  }

  getYear(): string {
    if (!this.orderData?.item) return '';
    const texto = this.orderData.item.toUpperCase();
    const match = texto.match(/AÑO:\s*([^\n-]+)/);
    return match ? match[1].trim() : '';
  }

  getPlate(): string {
    if (!this.orderData?.item) return '';
    const texto = this.orderData.item.toUpperCase();
    const match = texto.match(/PLACA\s*(?:N[°º]\s*)?:\s*([A-Z0-9-]+)/);
    return match ? match[1].trim() : '';
  }

 getDescripcion() {
    if (!this.orderData?.item) return '';
    const texto = this.orderData.item.toUpperCase();

    return texto.split(',')[0].trim();
  }
}