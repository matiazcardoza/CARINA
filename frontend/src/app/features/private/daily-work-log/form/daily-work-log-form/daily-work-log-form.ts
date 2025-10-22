import { Component, Inject, inject, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogRef, MatDialogModule } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { NgxMaterialTimepickerModule } from 'ngx-material-timepicker';
import { MatNativeDateModule } from '@angular/material/core';
import { CommonModule } from '@angular/common';
import { ChangeDetectorRef } from '@angular/core';
import { DailyWorkLogService } from '../../../../../services/DailyWorkLogService/daily-work-log-service';
import { MatSelectModule } from '@angular/material/select';
import { MatAutocompleteModule } from '@angular/material/autocomplete';

import { startWith, map } from 'rxjs/operators';
import { FormControl } from '@angular/forms';

import { ProductsService, ProductsElement } from '../../../../../services/productsService/products-service';
import { OperatorsService, OperatorsElement } from '../../../../../services/OperatorsService/operators-service';
import { MatIconModule } from '@angular/material/icon';

export interface DialogData {
  isEdit: boolean;
  workLog: any;
  serviceId?: string | number;
  serviceState?: string | number;
  selectedDateFromFilter?: Date;
  selectedShift?: string | number;
}

@Component({
  selector: 'app-daily-parts-form',
  templateUrl: './daily-work-log-form.html',
  styleUrls: ['./daily-work-log-form.css'],
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatDialogModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
    MatDatepickerModule,
    MatNativeDateModule,
    MatSelectModule,
    MatAutocompleteModule,
    MatIconModule,
    NgxMaterialTimepickerModule
  ],
  standalone: true,
  providers: [
    DailyWorkLogService,
    ProductsService
  ]
})
export class DailyWorkLogForm implements OnInit {

  workLogForm: FormGroup;
  isLoading = false;

  products: ProductsElement[] = [];
  operators: OperatorsElement[] = [];

  filteredProducts: ProductsElement[] = [];
  private productsControl = new FormControl();
  isLoadingProducts = false;
  isLoadingOperators = false;
  filteredOperators: OperatorsElement[] = [];
  private operatorsControl = new FormControl();

  selectedFiles: File[] = [];
  previewUrls: string[] = [];
  existingImages: any[] = [];
  maxFiles = 5;
  maxFileSize = 5 * 1024 * 1024;
  allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

  private fb = inject(FormBuilder);
  private dailyWorkLogService = inject(DailyWorkLogService);
  private productsService = inject(ProductsService);
  private operatorsService = inject(OperatorsService);

  constructor(
    public dialogRef: MatDialogRef<DailyWorkLogForm>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData,
    private cdr: ChangeDetectorRef
  ) {
    // Configurar validadores dinámicamente basándose en serviceState
    const productValidators = this.shouldRequireProductFields ? [Validators.required] : [];
    const fuelValidators = this.shouldRequireProductFields ? [Validators.required, Validators.min(0)] : [];

    const formConfig: any = {
      work_date: ['', Validators.required],
      start_time: [''],
      initial_fuel: [''],
      product_id: [''],
      description: ['', Validators.required],
      operator_id: ['', Validators.required]
    };

    if (this.isStateTwo) {
      formConfig.end_time = [''];
      formConfig.occurrences = [''];
    }

    this.workLogForm = this.fb.group(formConfig);
  }

  get isStateTwo(): boolean {
    return this.data.workLog?.state === 2 || this.data.workLog?.state === 4 || this.data.workLog?.state === 3;
  }

  // Getter para determinar si los campos de producto son requeridos
  get shouldRequireProductFields(): boolean {
    return this.data.serviceState !== 2 && this.data.serviceState !== '2';
  }

  // Getter para mostrar/ocultar campos de producto
  get shouldShowProductFields(): boolean {
    //return this.shouldRequireProductFields;
    return false;
  }

  ngOnInit() {
    // Solo cargar productos si son necesarios
    /*if (this.shouldRequireProductFields) {
      this.loadProducts();
      this.setupProductFieldLogic();
    } else {
      // Si no se requieren campos de producto, habilitamos directamente el combustible
      this.workLogForm.get('initial_fuel')?.disable();
    }*/
    this.loadOperators();
    this.workLogForm.get('initial_fuel')?.enable();
    this.setupFormValues();
    if (!this.data.isEdit && !this.isStateTwo) {
      this.setCurrentTime();
    }
  }

  private setCurrentTime() {
    const now = new Date();
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const currentTime = `${hours}:${minutes}`;
    this.workLogForm.patchValue({
      start_time: currentTime
    });
    this.cdr.detectChanges(); 
  }

  private setupProductFieldLogic() {
    this.workLogForm.get('initial_fuel')?.disable();

    this.workLogForm.get('product_id')?.valueChanges.pipe(
      startWith(''),
      map(value => (typeof value === 'string' ? value : value?.numero))
    ).subscribe(numero => {
      this.filteredProducts = this._filter(numero || '');
      const currentValue = this.workLogForm.get('product_id')?.value;
      if (currentValue && typeof currentValue === 'object' && currentValue.id) {
        this.workLogForm.get('initial_fuel')?.enable();
      } else {
        this.workLogForm.get('initial_fuel')?.disable();
        this.workLogForm.get('initial_fuel')?.setValue('');
      }
    });
  }

  private convertTo12HourFormat(time24: string): string {
    if (!time24) return '';
    
    const [hours, minutes] = time24.split(':').map(Number);
    const period = hours >= 12 ? 'PM' : 'AM';
    const hours12 = hours % 12 || 12;
    
    return `${hours12.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')} ${period}`;
  }

  private setupFormValues() {
    if (this.data.isEdit && this.data.workLog) {
      let adjustedWorkDate: Date | null = null;
      if (this.data.workLog.work_date) {
        const dateString = this.data.workLog.work_date;
        const parts = dateString.split('-');
        adjustedWorkDate = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
      }
      const formValues: any = {
        work_date: adjustedWorkDate,
        start_time: this.convertTo12HourFormat(this.data.workLog.start_time),
        description: this.data.workLog.description || '',
        initial_fuel: this.data.workLog.initial_fuel,
        operator_id: this.data.workLog.operator
      };

      // Solo agregar campos de producto si son requeridos
      /*if (this.shouldRequireProductFields) {
        const product = { id: this.data.workLog.products_id, numero: this.data.workLog.numero, item: this.data.workLog.item } as ProductsElement;
        formValues.product_id = product;
        formValues.initial_fuel = this.data.workLog.initial_fuel;
        this.workLogForm.get('initial_fuel')?.enable();
      }*/
     if (this.isStateTwo) {
        formValues.end_time = this.convertTo12HourFormat(this.data.workLog.end_time || '');
        formValues.occurrences = this.data.workLog.occurrences || '';

        // Cargar imágenes existentes si las hay
        if (this.data.workLog.images && Array.isArray(this.data.workLog.images)) {
          this.existingImages = this.data.workLog.images;
        }
      }

      this.workLogForm.patchValue(formValues);
      //this.workLogForm.get('work_date')?.disable();
      //this.workLogForm.get('start_time')?.disable();
    } else {
      const initialDate = this.data.selectedDateFromFilter;
      console.log(initialDate);
      this.workLogForm.patchValue({
        work_date: initialDate
      });

      //this.workLogForm.get('work_date')?.disable();
      //this.workLogForm.get('start_time')?.disable();
    }
  }

  get isProductSelected(): boolean {
    return true;
    /*if (!this.shouldRequireProductFields) return true;
    const product = this.workLogForm.get('product_id')?.value;
    return product && typeof product === 'object' && product.id;*/
  }

  onProductSelected(product: ProductsElement) {
    this.workLogForm.get('product_id')?.setValue(product);
    this.workLogForm.get('initial_fuel')?.enable();
  }

  displayProduct(product: ProductsElement | null): string {
    return product ? `${product.numero} - ${product.item}` : '';
  }

  displayOperator(operator: OperatorsElement | null): string {
    return operator ? `${operator.name}` : '';
  }

  private _filter(value: string): ProductsElement[] {
    const filterValue = value.toLowerCase();
    return this.products.filter(product =>
      product.numero.toLowerCase().includes(filterValue) ||
      product.item.toLowerCase().includes(filterValue)
    );
  }

  private loadProducts() {
    this.isLoadingProducts = true;
    this.productsService.getProducts().subscribe({
      next: (products) => {
        console.log('Productos cargados:', products);
        this.products = products;
        this.isLoadingProducts = false;
        this.filteredProducts = [...this.products];
        this.cdr.detectChanges();
      },
      error: (error) => {
        console.error('Error al cargar productos:', error);
        this.isLoadingProducts = false;
        this.cdr.detectChanges();
      }
    });
  }

  private loadOperators() {
    this.isLoadingOperators  = true;
    const serviceId = this.data.serviceId ? this.data.serviceId.toString() : '';
    this.operatorsService.getOperators(serviceId).subscribe({
      next: (operators) => {
        console.log('Operadores cargados:', operators);
        this.operators = operators;
        if (this.operators.length > 0 && !this.data.isEdit) {
          this.workLogForm.get('operator_id')?.setValue(this.operators[0].id);
        }else{
          this.workLogForm.get('operator_id')?.setValue(this.data.workLog.operator_id);
        }
        this.isLoadingOperators = false;
        this.cdr.detectChanges();
      },
      error: (error) => {
        console.error('Error al cargar operadores:', error);
        this.isLoadingOperators = false;
        this.cdr.detectChanges();
      }
    });
  }

  get title(): string {
    return this.data.isEdit ? 'Editar Registro de Trabajo' : 'Nuevo Registro de Trabajo';
  }

  get submitButtonText(): string {
    return this.data.isEdit ? 'Actualizar' : 'Crear';
  }

  onCancel() {
    this.dialogRef.close(false);
  }

  onSubmit() {
    if (this.workLogForm.valid && !this.isLoading) {
      this.isLoading = true;

      const formValue = this.workLogForm.getRawValue();

      // ← CAMBIAR A FormData SI ES ESTADO 2 Y HAY IMÁGENES
      if (this.isStateTwo && (this.selectedFiles.length > 0 || this.existingImages.length > 0)) {
        const formData = new FormData();

        formData.append('id', this.data.workLog?.id.toString());
        formData.append('work_date', this.formatDate(formValue.work_date));
        formData.append('start_time', formValue.start_time);
        formData.append('description', formValue.description);
        formData.append('initial_fuel', formValue.initial_fuel);
        formData.append('operator_id', formValue.operator_id.id.toString());

        formData.append('service_id', this.data.serviceId ? this.data.serviceId.toString() : '');

        formData.append('end_time', formValue.end_time);
        formData.append('occurrences', formValue.occurrences || '');

        if (this.data.selectedShift) {
          formData.append('shift_id', this.data.selectedShift.toString());
        }

        // Agregar IDs de imágenes existentes que se mantienen
        this.existingImages.forEach((img, index) => {
          formData.append(`existing_images[${index}]`, img.id.toString());
        });

        // Agregar nuevas imágenes
        this.selectedFiles.forEach((file) => {
          formData.append('images[]', file);
        });

        this.submitWorkLogData(formData);
      } else {
        // Lógica original para estado 1
        const workLogData: any = {
          id: this.data.workLog?.id,
          work_date: this.formatDate(formValue.work_date),
          start_time: formValue.start_time,
          description: formValue.description,
          service_id: this.data.serviceId ? Number(this.data.serviceId) : null,
          initial_fuel: parseFloat(formValue.initial_fuel),
          operator_id: formValue.operator_id
        };

        if (this.data.selectedShift) {
          workLogData.shift_id = this.data.selectedShift;
        }

        // ← AGREGAR CAMPOS SI ES ESTADO 2
        if (this.isStateTwo) {
          workLogData.end_time = formValue.end_time;
          workLogData.occurrences = formValue.occurrences || '';
        }

        this.submitWorkLogData(workLogData);
      }
    }
  }

  private submitWorkLogData(workLogData: any) {
    if (this.data.isEdit && this.data.workLog?.id) {
      setTimeout(() => {
        this.dailyWorkLogService.updateWorkLog(workLogData)
          .subscribe({
            next: (updatedWorkLog) => {
              this.isLoading = false;
              this.cdr.detectChanges();
              setTimeout(() => {
                this.dialogRef.close(updatedWorkLog);
              }, 100);
            },
            error: (error) => {
              this.isLoading = false;
              this.cdr.detectChanges();
              console.error('Error al actualizar:', error);
            }
          });
      }, 0);
    } else {
      setTimeout(() => {
        console.log('crear workLogData:', workLogData);
        this.dailyWorkLogService.createWorkLog(workLogData)
          .subscribe({
            next: (newWorkLog) => {
              this.isLoading = false;
              this.cdr.detectChanges();
              setTimeout(() => {
                this.dialogRef.close(newWorkLog);
              }, 100);
            },
            error: (error) => {
              this.isLoading = false;
              this.cdr.detectChanges();
              console.error('Error al crear:', error);
            }
          });
      }, 0);
    }
  }

  private formatDate(date: Date): string {
    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  private compareTime(time1: string, time2: string): number {
    const [h1, m1] = time1.split(':').map(Number);
    const [h2, m2] = time2.split(':').map(Number);

    const minutes1 = h1 * 60 + m1;
    const minutes2 = h2 * 60 + m2;

    return minutes1 - minutes2;
  }

  get workDateError() {
    const control = this.workLogForm.get('work_date');
    if (control?.hasError('required') && control?.touched) {
      return 'La fecha de trabajo es requerida';
    }
    return '';
  }

  get startTimeError() {
    const control = this.workLogForm.get('start_time');
    if (control?.hasError('required') && control?.touched) {
      return 'La hora inicial es requerida';
    }
    return '';
  }

  get productError() {
    if (!this.shouldRequireProductFields) return '';
    const control = this.workLogForm.get('product_id');
    if (control?.hasError('required') && control?.touched) {
      return 'El producto es requerido';
    }
    return '';
  }

  get initialFuelError() {
    if (!this.shouldRequireProductFields) return '';
    const control = this.workLogForm.get('initial_fuel');
    if (control?.hasError('required') && control?.touched) {
      return 'El combustible inicial es requerido';
    }
    if (control?.hasError('min') && control?.touched) {
      return 'El combustible inicial debe ser mayor o igual a 0';
    }
    return '';
  }

  get descriptionError() {
    const control = this.workLogForm.get('description');
    return '';
  }

  get endTimeError() {
    if (!this.isStateTwo) return '';
    const control = this.workLogForm.get('end_time');
    if (control?.hasError('required') && control?.touched) {
      return 'La hora de finalización es requerida';
    }
    return '';
  }

  get occurrenceError() {
    if (!this.isStateTwo) return '';
    return '';
  }

  onFileSelect(event: any) {
    const files = Array.from(event.target.files) as File[];

    if (this.selectedFiles.length + files.length > this.maxFiles) {
      alert(`Solo puedes subir un máximo de ${this.maxFiles} imágenes`);
      return;
    }

    for (const file of files) {
      if (!this.validateFile(file)) {
        continue;
      }

      this.selectedFiles.push(file);

      const reader = new FileReader();
      reader.onload = (e: any) => {
        this.previewUrls.push(e.target.result);
        this.cdr.detectChanges();
      };
      reader.readAsDataURL(file);
    }

    event.target.value = '';
  }

  private validateFile(file: File): boolean {
    if (!this.allowedTypes.includes(file.type)) {
      alert(`Tipo de archivo no permitido: ${file.name}. Solo se permiten imágenes JPG, PNG y WebP.`);
      return false;
    }

    if (file.size > this.maxFileSize) {
      alert(`El archivo ${file.name} es muy grande. El tamaño máximo es 5MB.`);
      return false;
    }

    return true;
  }

  removeFile(index: number) {
    this.selectedFiles.splice(index, 1);
    this.previewUrls.splice(index, 1);
    this.cdr.detectChanges();
  }

  removeExistingImage(index: number) {
    this.existingImages.splice(index, 1);
    this.cdr.detectChanges();
  }

  formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }
}
