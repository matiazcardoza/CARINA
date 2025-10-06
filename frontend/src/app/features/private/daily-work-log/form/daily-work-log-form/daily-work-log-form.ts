import { Component, Inject, inject, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogRef, MatDialogModule } from '@angular/material/dialog';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatNativeDateModule } from '@angular/material/core';
import { CommonModule } from '@angular/common';
import { ChangeDetectorRef } from '@angular/core';
import { DailyWorkLogService } from '../../../../../services/DailyWorkLogService/daily-work-log-service';
import { MatSelectModule } from '@angular/material/select';
import { MatAutocompleteModule } from '@angular/material/autocomplete';

import { startWith, map } from 'rxjs/operators';
import { FormControl } from '@angular/forms';

import { ProductsService, ProductsElement } from '../../../../../services/productsService/products-service';

export interface DialogData {
  isEdit: boolean;
  workLog: any;
  serviceId?: string | number;
  serviceState?: string | number;
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
    MatAutocompleteModule
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

  filteredProducts: ProductsElement[] = [];
  private productsControl = new FormControl();

  isLoadingProducts = false;

  private fb = inject(FormBuilder);
  private dailyWorkLogService = inject(DailyWorkLogService);
  private productsService = inject(ProductsService);

  constructor(
    public dialogRef: MatDialogRef<DailyWorkLogForm>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData,
    private cdr: ChangeDetectorRef
  ) {
    // Configurar validadores dinámicamente basándose en serviceState
    const productValidators = this.shouldRequireProductFields ? [Validators.required] : [];
    const fuelValidators = this.shouldRequireProductFields ? [Validators.required, Validators.min(0)] : [];

    this.workLogForm = this.fb.group({
      work_date: ['', Validators.required],
      start_time: ['', Validators.required],
      initial_fuel: [''],
      product_id: [''],
      description: ['', Validators.required]
    });
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
    this.workLogForm.get('initial_fuel')?.enable();

    this.setupFormValues();
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

  private setupFormValues() {
    if (this.data.isEdit && this.data.workLog) {
      const formValues: any = {
        work_date: this.data.workLog.work_date ? new Date(this.data.workLog.work_date) : null,
        start_time: this.data.workLog.start_time,
        description: this.data.workLog.description || ''
      };

      // Solo agregar campos de producto si son requeridos
      /*if (this.shouldRequireProductFields) {
        const product = { id: this.data.workLog.products_id, numero: this.data.workLog.numero, item: this.data.workLog.item } as ProductsElement;
        formValues.product_id = product;
        formValues.initial_fuel = this.data.workLog.initial_fuel;
        this.workLogForm.get('initial_fuel')?.enable();
      }*/
      formValues.initial_fuel = this.data.workLog.initial_fuel;

      this.workLogForm.patchValue(formValues);
      //this.workLogForm.get('work_date')?.disable();
      //this.workLogForm.get('start_time')?.disable();
    } else {
      const now = new Date();
      this.workLogForm.patchValue({
        work_date: now
      });
      this.setCurrentTime();

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
      
      const workLogData: any = {
        id: this.data.workLog?.id,
        work_date: this.formatDate(formValue.work_date),
        start_time: formValue.start_time,
        description: formValue.description,
        service_id: this.data.serviceId ? Number(this.data.serviceId) : null,


        initial_fuel: parseFloat(formValue.initial_fuel)
      };

      // Solo agregar campos de producto si son requeridos
      /*if (this.shouldRequireProductFields) {
        const productObject = formValue.product_id;
        workLogData.initial_fuel = parseFloat(formValue.initial_fuel);
        workLogData.product_id = productObject.id;
      }*/

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
}