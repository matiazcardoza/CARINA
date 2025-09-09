// src/app/fuel-order-form/fuel-order-form.component.ts
import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';

import { DatePickerModule } from 'primeng/datepicker';
import { InputTextModule } from 'primeng/inputtext';
import { InputNumberModule } from 'primeng/inputnumber';
import { TextareaModule } from 'primeng/textarea';
import { InputGroupModule } from 'primeng/inputgroup';
import { InputGroupAddonModule } from 'primeng/inputgroupaddon';
import { ButtonModule } from 'primeng/button';
import { SelectModule } from 'primeng/select'; // 👈 select moderno de PrimeNG

@Component({
  selector: 'app-fuel-vouchers',
  standalone: true,
  imports: [
    CommonModule, ReactiveFormsModule,
    DatePickerModule, InputTextModule, InputNumberModule,
    TextareaModule, InputGroupModule, InputGroupAddonModule,
    ButtonModule, SelectModule
  ],
  templateUrl: './fuel-vouchers.html',
  styleUrl: './fuel-vouchers.css'
})
export class FuelVouchers {
  private fb = inject(FormBuilder);

  // Opciones para el select
  fuelOptions = [
    { label: 'Gasolina', value: 'Gasolina' },
    { label: 'Petróleo', value: 'Petróleo' },
    { label: 'Otro',     value: 'Otro' }
  ];

  form = this.fb.group({
    numero: ['', Validators.required],
    fecha: [new Date(), Validators.required],

    ordenCompra: [''],
    componente: [''],
    grifo: [''],
    chofer: [''],

    // 👇 Un solo bloque de combustible
    combustible: this.fb.group({
      tipo: ['', Validators.required],
      glns: [null],
      precio: [null]
    }),

    vehiculo: this.fb.group({
      marca: [''],
      placa: ['', Validators.required],
      dependencia: [''],
      motivo: [''],
      hojaViaje: ['']
    })
  });

  onSubmit() {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    console.log('Payload listo:', this.form.getRawValue());
  }
}
