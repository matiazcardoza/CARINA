import { Component, OnInit, ChangeDetectorRef, Inject } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule, FormArray } from '@angular/forms';
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
import { MatTooltipModule } from '@angular/material/tooltip';

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
  idmedida: number;
  name_catalog: string;
  idserviciodet: number;
  isNew?: boolean;
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
    MatOptionModule,
    MatTooltipModule
  ]
})
export class DailyWorkLogReceive implements OnInit {
  orderForm!: FormGroup;
  importForm!: FormGroup;
  operators!: FormArray;
  numeroOrdenErrors: string | null = null;
  isLoading = false;
  orderData: OrderDetail[] = [];
  selectedItem: OrderDetail | null = null;
  showResults = false;
  anioOptions: number[] = [];

  itemFormData: Map<number, any> = new Map();

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
    // Generar opciones de años (últimos 5 años + año actual)
    const currentYear = new Date().getFullYear();
    for (let i = 0; i <= 5; i++) {
      this.anioOptions.push(currentYear - i);
    }

    this.orderForm = this.fb.group({
      numeroOrden: ['', [Validators.required, Validators.pattern(/^\d{5}$/)]],
      anio: [currentYear, Validators.required]
    });

    this.importForm = this.fb.group({
      operators: this.fb.array([]),
      tipoMaquinaria: ['', Validators.required],
      maquinaria: ['', Validators.required],
      marca: ['', Validators.required],
      modelo: ['', Validators.required],
      capacidad: ['', Validators.required],
      year: ['', Validators.required],
      serie: ['', Validators.required],
      placa: ['', Validators.required],
    });

    this.operators = this.importForm.get('operators') as FormArray;
    this.addOperator();

    this.importForm.valueChanges.subscribe(() => {
      this.saveCurrentItemData();
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
    const anio = this.orderForm.get('anio')?.value;
    this.isLoading = true;
    this.showResults = false;
    this.orderData = [];

    this.dailyWorkLogService.getOrderByNumber(numeroOrden, anio).subscribe({
      next: (response: OrderResponse) => {
        if (response.data && response.data.length > 0) {
          this.orderData = response.data;
          this.selectedItem = response.data[0];
          this.showResults = true;
          this.numeroOrdenErrors = null;
          this.loadItemData(this.selectedItem);
        } else {
          this.numeroOrdenErrors = 'No se encontraron datos para esta orden.';
          this.orderData = [];
          this.selectedItem = null;
          this.showResults = false;
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error('Error al buscar la orden:', err);
        this.numeroOrdenErrors = 'Ocurrió un error al buscar la orden. Intente de nuevo.';
        this.orderData = [];
        this.selectedItem = null
        this.showResults = false;
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  loadItemData(item: OrderDetail): void {
    const savedData = this.itemFormData.get(item.idserviciodet);
    if (savedData) {
      this.importForm.patchValue({
        tipoMaquinaria: savedData.tipoMaquinaria,
        maquinaria: savedData.maquinaria,
        marca: savedData.marca,
        modelo: savedData.modelo,
        capacidad: savedData.capacidad,
        year: savedData.year,
        serie: savedData.serie,
        placa: savedData.placa,
      });
      this.operators.clear();
      if (savedData.operators && savedData.operators.length > 0) {
        savedData.operators.forEach((op: any) => {
          this.operators.push(this.createOperatorGroup(op.operatorName));
        });
      } else {
        this.addOperator();
      }
    } else {
      const operatorText = this.getOperator(item);
      const parsedData = {
        tipoMaquinaria: this.getTipoMaquinaria(item),
        maquinaria: this.getMachineryEquipment(item),
        marca: this.getBrand(item),
        modelo: this.getModel(item),
        capacidad: this.getAbility(item),
        year: this.getYear(item),
        serie: this.getNumberSerial(item),
        placa: this.getPlate(item),
        operators: []
      };

      this.importForm.patchValue(parsedData);
      this.operators.clear();
      if (operatorText) {
        this.operators.push(this.createOperatorGroup(operatorText));
      } else {
        this.addOperator();
      }
      parsedData.operators = this.operators.value;
      this.itemFormData.set(item.idserviciodet, parsedData);
    }
  }

  addNewItem(): void {
    this.saveCurrentItemData();
    
    // Crear un nuevo item basado en el primer item existente
    const firstItem = this.orderData[0];
    const newItem: OrderDetail = {
      idservicio: firstItem.idservicio,
      ruc: firstItem.ruc,
      rsocial: firstItem.rsocial,
      anio: firstItem.anio,
      numero: firstItem.numero,
      siaf: firstItem.siaf,
      fecha_prestacion: firstItem.fecha_prestacion,
      plazo_prestacion: firstItem.plazo_prestacion,
      cod_meta: firstItem.cod_meta,
      desmeta: firstItem.desmeta,
      item: '',
      idmeta: firstItem.idmeta,
      idmedida: firstItem.idmedida,
      name_catalog: 'Nuevo Item',
      idserviciodet: Date.now(),
      isNew: true
    };
    
    this.orderData.push(newItem);
    this.selectedItem = newItem;

    this.operators.clear();
    this.addOperator();
    
    this.importForm.reset();
    this.itemFormData.set(newItem.idserviciodet, {
      operador: '',
      tipoMaquinaria: '',
      maquinaria: '',
      marca: '',
      modelo: '',
      capacidad: '',
      year: '',
      serie: '',
      placa: ''
    });
  }

  saveCurrentItemData(): void {
    if (this.selectedItem) {
      const formValue = {
        ...this.importForm.value,
        operators: this.operators.value
      };
      this.itemFormData.set(this.selectedItem.idserviciodet, formValue);
    }
  }

  selectItem(item: OrderDetail): void {
    this.saveCurrentItemData();
    this.selectedItem = item;
    this.loadItemData(item);
  }

  clearData(): void {
    this.orderForm.reset();
    const currentYear = new Date().getFullYear();
    this.orderForm.patchValue({ anio: currentYear });
    
    this.importForm.reset();
    this.numeroOrdenErrors = null;
    this.orderData = [];
    this.selectedItem = null;
    this.showResults = false;
    this.itemFormData.clear();
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

  areAllItemsValid(): boolean {
    if (this.orderData.length === 0) {
      return false;
    }

    this.saveCurrentItemData();

    for (const item of this.orderData) {
      const itemData = this.itemFormData.get(item.idserviciodet);
      
      if (!itemData) {
        return false;
      }
      if (!itemData.operators || itemData.operators.length === 0) {
        return false;
      }
      for (const op of itemData.operators) {
        if (!op.operatorName || op.operatorName.trim() === '') {
          return false;
        }
      }
      if (!itemData.tipoMaquinaria || !itemData.maquinaria || 
          !itemData.marca || !itemData.modelo || !itemData.capacidad || 
          !itemData.year || !itemData.serie || !itemData.placa) {
        return false;
      }
    }
    return true;
  }

  importOrder(): void {
    this.importForm.markAllAsTouched();
    if (!this.areAllItemsValid()) {
      return;
    }
    if (this.orderData.length === 0 || this.importForm.invalid) {
      return;
    }

    this.isLoading = true;
    
    const commonFormValues = this.importForm.value;
    const firstItem = this.orderData[0];

    // Calcular fecha final basada en el primer item
    const fechaInicio = new Date(firstItem.fecha_prestacion);
    const fechaFinal = new Date(fechaInicio);
    fechaFinal.setDate(fechaInicio.getDate() + firstItem.plazo_prestacion);

    // Datos comunes de la orden (se toman del primer item ya que son iguales para todos)
    const orderData = {
      idservicio: firstItem.idservicio,
      idmeta: firstItem.idmeta,
      ruc: firstItem.ruc,
      rsocial: firstItem.rsocial,
      cod_meta: firstItem.cod_meta,
      desmeta: firstItem.desmeta,
      fechaPrestacion: firstItem.fecha_prestacion,
      plazoPrestacion: firstItem.plazo_prestacion,
      numero_orden: firstItem.numero,
      anio_orden: firstItem.anio,
      fechaFinal: fechaFinal.toISOString().split('T')[0]
    };

    console.log('importando datos de la orden:', orderData);
    
    // Items individuales con sus datos específicos
    const items = this.orderData.map(item => {
      // Obtener los datos guardados del formulario para este item
      const itemData = this.itemFormData.get(item.idserviciodet) || this.importForm.value;
      
      return {
        idserviciodet: item.idserviciodet,
        medida_id: item.idmedida,
        machinery_equipment: itemData.maquinaria,
        ability: itemData.capacidad,
        brand: itemData.marca,
        model: itemData.modelo,
        serial_number: itemData.serie,
        year: itemData.year,
        plate: itemData.placa,
        operators: itemData.operators || [],
        tipoMaquinaria: itemData.tipoMaquinaria,
        description: item.item || ''
      };
    });

    // Payload final: 1 orden con múltiples items
    const payload = {
      order: orderData,
      items: items
    };

    this.dailyWorkLogService.importOrder(payload).subscribe({
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

  private createOperatorGroup(operatorName: string = ''): FormGroup {
    return this.fb.group({
      operatorName: [operatorName, Validators.required]
    });
  }

  addOperator(): void {
    this.operators.push(this.createOperatorGroup());
    this.cdr.detectChanges();
  }

  removeOperator(index: number): void {
    if (this.operators.length > 1) {
      this.operators.removeAt(index);
      this.cdr.detectChanges();
    }
  }

  get operatorsControls() {
    return this.operators.controls;
  }

  getOperator(item: OrderDetail): string {
    if (!item?.item) return '';
    const texto = item.item.toUpperCase();
    const match = texto.match(/OPERADOR\s*\d*:\s*([^\n-]+)/);
    return match ? match[1].trim() : '';
  }
  
  getMachineryEquipment(item: OrderDetail): string {
    if (!item?.item) return '';
    const texto = item.item.toUpperCase();
    const match = texto.match(/(?:ALQUILER|SERVICIO)\s+DE\s+([^,]+)/);
    return match ? match[1].trim() : '';
  }

  getTipoMaquinaria(item: OrderDetail): number | null {
    if (!item?.item) return null;
    const texto = item.item.toUpperCase();
    if (texto.includes('MAQUINA SECA')) return 1;
    if (texto.includes('MAQUINA SERVIDA')) return 2;
    return null;
  }

  getAbility(item: OrderDetail): string {
    if (!item?.item) return '';
    const texto = item.item.toUpperCase();
    const match = texto.match(/MOTOR:\s*([^\n-]+).*POTENCIA:\s*([^\n-]+)/s);
    return match ? `${match[1].trim()} - ${match[2].trim()}` : '';
  }

  getBrand(item: OrderDetail): string {
    if (!item?.item) return '';
    const texto = item.item.toUpperCase();
    const match = texto.match(/MARCA:\s*([^\n-]+)/);
    return match ? match[1].trim() : '';
  }

  getModel(item: OrderDetail): string {
    if (!item?.item) return '';
    const texto = item.item.toUpperCase();
    const match = texto.match(/MODELO:\s*([^\n-]+)/);
    return match ? match[1].trim() : '';
  }

  getNumberSerial(item: OrderDetail): string {
    if (!item?.item) return '';
    const texto = item.item.toUpperCase();
    const match = texto.match(/SERIE:\s*([^\n-]+)/);
    return match ? match[1].trim() : '';
  }

  getYear(item: OrderDetail): string {
    if (!item?.item) return '';
    const texto = item.item.toUpperCase();
    const match = texto.match(/AÑO:\s*([^\n-]+)/);
    return match ? match[1].trim() : '';
  }

  getPlate(item: OrderDetail): string {
    if (!item?.item) return '';
    const texto = item.item.toUpperCase();
    const match = texto.match(/PLACA\s*(?:N[°º]\s*)?:\s*([A-Z0-9-]+)/);
    return match ? match[1].trim() : '';
  }

  getDescripcion(item: OrderDetail): string {
    if (!item?.item) return '';
    const texto = item.item.toUpperCase();
    return texto.split(',')[0].trim();
  }

  getTooltipContent(item: OrderDetail): string {
    const placa = this.getPlate(item) || 'Sin placa';
    const descripcion = this.getShortDescription(item);
    return `${descripcion}\nPlaca: ${placa}`;
  }

  getShortDescription(item: OrderDetail): string {
    if (!item?.name_catalog) return 'Item';
    const description = item.name_catalog;
    return description.length > 30 ? description.substring(0, 30) + '...' : description;
  }
}