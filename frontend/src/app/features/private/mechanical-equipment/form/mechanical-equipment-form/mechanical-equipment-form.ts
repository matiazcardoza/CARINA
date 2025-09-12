import { Component, Inject, inject, OnInit, ViewEncapsulation } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogRef, MatDialogModule } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { ChangeDetectorRef } from '@angular/core';
import { MatSelectModule } from '@angular/material/select';
import { CommonModule } from '@angular/common';

import { MechanicalEquipmentService } from '../../../../../services/MechanicalEquipmentService/mechanical-equipment-service';

export interface DialogData {
  isEdit: boolean;
  mechanicalEquipment: any;
}

@Component({
  selector: 'app-mechanical-equipment-form',
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatSelectModule,
  ],
  templateUrl: './mechanical-equipment-form.html',
  styleUrl: './mechanical-equipment-form.css',
  encapsulation: ViewEncapsulation.None
})
export class MechanicalEquipmentForm implements OnInit {
  mechanicalEquipmentForm: FormGroup;
  isLoading = false;

  stateOptions = [
    { value: 1, label: 'Operativo' },
    { value: 2, label: 'En Mantenimiento' },
    { value: 3, label: 'Averiado' },
    { value: 4, label: 'Fuera de Servicio' }
  ];
  
  private fb = inject(FormBuilder);
  private mechanicalEquipmentService = inject(MechanicalEquipmentService);

  constructor(
    public dialogRef: MatDialogRef<MechanicalEquipmentForm>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData,
    private cdr: ChangeDetectorRef
  ) {
    this.mechanicalEquipmentForm = this.fb.group({
      machinery_equipment: ['', [Validators.required, Validators.maxLength(100)]],
      ability: ['', [Validators.required, Validators.maxLength(50)]],
      brand: ['', [Validators.required, Validators.maxLength(50)]],
      model: ['', [Validators.required, Validators.maxLength(50)]],
      serial_number: ['', [Validators.required, Validators.maxLength(50)]],
      year: ['', [Validators.required, Validators.min(1900), Validators.max(new Date().getFullYear())]],
      plate: ['', Validators.maxLength(20)],
      state: ['', Validators.required],
      cost_hour: [null, [Validators.required, Validators.min(0)]]
    });
  }
  
  ngOnInit() {
    if (this.data.isEdit && this.data.mechanicalEquipment) {
      this.mechanicalEquipmentForm.patchValue({
        machinery_equipment: this.data.mechanicalEquipment.machinery_equipment,
        ability: this.data.mechanicalEquipment.ability,
        brand: this.data.mechanicalEquipment.brand,
        model: this.data.mechanicalEquipment.model,
        serial_number: this.data.mechanicalEquipment.serial_number,
        year: this.data.mechanicalEquipment.year,
        plate: this.data.mechanicalEquipment.plate,
        state: this.data.mechanicalEquipment.state,
        cost_hour: this.data.mechanicalEquipment.cost_hour
      });
    }
  }

  onSubmit(): void {
    if (this.mechanicalEquipmentForm.valid) {
      this.isLoading = true;
      const rawData = this.mechanicalEquipmentForm.value;

      const formData = {
        ...rawData,
        id: this.data.isEdit && this.data.mechanicalEquipment ? this.data.mechanicalEquipment.id : undefined
      };
      

      if (this.data.isEdit && this.data.mechanicalEquipment?.id) {
        setTimeout(() => {
          this.mechanicalEquipmentService.updateMechanicalEquipment(formData)
            .subscribe({
              next: (updatedMechanicalEquipment) => {
                this.isLoading = false;
                this.cdr.detectChanges();
                setTimeout(() => {
                  this.dialogRef.close(updatedMechanicalEquipment);
                }, 100);
              },
              error: (error) => {
                this.isLoading = false;
                this.cdr.detectChanges();
              }
          });
        }, 0);
      } else {
        setTimeout(() => {
          this.mechanicalEquipmentService.createMechanicalEquipment(formData)
            .subscribe({
              next: (newMechanicalEquipment) => {
                this.isLoading = false;
                this.cdr.detectChanges();
                setTimeout(() => {
                  this.dialogRef.close(newMechanicalEquipment);
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

  // Getters para manejo de errores
  get machinery_equipmentError(): string {
    const control = this.mechanicalEquipmentForm.get('machinery_equipment');
    if (control?.hasError('required')) return 'El equipo es requerido';
    if (control?.hasError('maxlength')) return 'Máximo 100 caracteres';
    return '';
  }

  get abilityError(): string {
    const control = this.mechanicalEquipmentForm.get('ability');
    if (control?.hasError('required')) return 'La capacidad es requerida';
    if (control?.hasError('maxlength')) return 'Máximo 50 caracteres';
    return '';
  }

  get brandError(): string {
    const control = this.mechanicalEquipmentForm.get('brand');
    if (control?.hasError('required')) return 'La marca es requerida';
    if (control?.hasError('maxlength')) return 'Máximo 50 caracteres';
    return '';
  }

  get modelError(): string {
    const control = this.mechanicalEquipmentForm.get('model');
    if (control?.hasError('required')) return 'El modelo es requerido';
    if (control?.hasError('maxlength')) return 'Máximo 50 caracteres';
    return '';
  }

  get serialNumberError(): string {
    const control = this.mechanicalEquipmentForm.get('serial_number');
    if (control?.hasError('required')) return 'El número de serie es requerido';
    if (control?.hasError('maxlength')) return 'Máximo 50 caracteres';
    return '';
  }

  get yearError(): string {
    const control = this.mechanicalEquipmentForm.get('year');
    if (control?.hasError('required')) return 'El año es requerido';
    if (control?.hasError('min')) return 'Año debe ser mayor a 1900';
    if (control?.hasError('max')) return `Año no puede ser mayor a ${new Date().getFullYear()}`;
    return '';
  }

  get plateError(): string {
    const control = this.mechanicalEquipmentForm.get('plate');
    if (control?.hasError('maxlength')) return 'Máximo 20 caracteres';
    return '';
  }

  get stateError(): string {
    const control = this.mechanicalEquipmentForm.get('state');
    if (control?.hasError('required')) return 'El estado es requerido';
    return '';
  }

  get costPerHourError(): string {
    const control = this.mechanicalEquipmentForm.get('cost_hour');
    if (control?.hasError('required')) return 'El costo por hora es requerido';
    if (control?.hasError('min')) return 'El costo no puede ser negativo';
    return '';
  }

  get title(): string {
    return this.data.isEdit ? 'Editar Equipo Mecánico' : 'Nuevo Equipo Mecánico';
  }

  get submitButtonText(): string {
    return this.data.isEdit ? 'Actualizar' : 'Crear';
  }

  onCancel() {
    this.dialogRef.close(false);
  }
}