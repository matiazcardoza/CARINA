// src/app/fuel-order-form/fuel-order-form.component.ts
import { Component, inject } from '@angular/core';

import { CommonModule } from '@angular/common';
import { FormArray, FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';

import { DatePickerModule } from 'primeng/datepicker';
import { InputTextModule } from 'primeng/inputtext';
import { InputNumberModule } from 'primeng/inputnumber';
import { TextareaModule } from 'primeng/textarea';
import { InputGroupModule } from 'primeng/inputgroup';
import { InputGroupAddonModule } from 'primeng/inputgroupaddon';
import { ButtonModule, Button } from 'primeng/button';

@Component({
  selector: 'app-fuel-vouchers',
    imports: [
    CommonModule, ReactiveFormsModule,
    DatePickerModule, InputTextModule, InputNumberModule,
    TextareaModule, InputGroupModule, InputGroupAddonModule,
    ButtonModule
  ],
  templateUrl: './fuel-vouchers.html',
  styleUrl: './fuel-vouchers.css'
})
export class FuelVouchers {
private fb = inject(FormBuilder);

  form = this.fb.group({
    numero: ['', Validators.required],
    fecha: [new Date(), Validators.required],

    ordenCompra: [''],
    componente: [''],
    grifo: [''],
    chofer: [''],

    combustibles: this.fb.array([
      this.newCombustible('Gasolina'),
      this.newCombustible('Petróleo'),
      this.newCombustible('Otro')
    ]),

    vehiculo: this.fb.group({
      marca: [''],
      placa: ['', Validators.required],
      dependencia: [''],
      motivo: [''],
      hojaViaje: ['']
    })
  });

  get combustibles(): FormArray<FormGroup> {
    return this.form.get('combustibles') as FormArray<FormGroup>;
  }

  private newCombustible(etiqueta: string) {
    return this.fb.group({
      etiqueta: [etiqueta],
      glns: [null],
      precio: [null] // S/. total o unitario según tu flujo
    });
  }

  onSubmit() {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    console.log('Payload listo:', this.form.getRawValue());
    // aquí envías al backend (Laravel) como prefieras
  }
}
