import { Component, inject, output } from '@angular/core';
// src/app/fuel-order-form/fuel-order-form.component.ts


import { CommonModule, formatDate } from '@angular/common';
import { FormArray, FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';

import { DatePickerModule } from 'primeng/datepicker';
import { InputTextModule } from 'primeng/inputtext';
import { InputNumberModule } from 'primeng/inputnumber';
import { TextareaModule } from 'primeng/textarea';
import { InputGroupModule } from 'primeng/inputgroup';
import { InputGroupAddonModule } from 'primeng/inputgroupaddon';
import { ButtonModule, Button } from 'primeng/button';
import { SelectModule } from 'primeng/select'; // 👈 nuevo
import { FuelVouchersService } from '../../services/fuel-vouchers.service';

@Component({
  selector: 'app-new-fuel-voucher',
  imports: [CommonModule, ReactiveFormsModule,
    DatePickerModule, InputTextModule, InputNumberModule,
    TextareaModule, InputGroupModule, InputGroupAddonModule,
    ButtonModule, SelectModule ],
  templateUrl: './new-fuel-voucher.html',
  styleUrl: './new-fuel-voucher.css'
})
export class NewFuelVoucher {
    private fb = inject(FormBuilder);
    private api = inject(FuelVouchersService);
    // saved = 
    saved = output<any>();
    // Opciones para el select (puedes mapear IDs si prefieres)
    fuelOptions = [
      { label: 'Gasolina',  value: 'Gasolina' },
      { label: 'Petróleo',  value: 'Petróleo' },
      { label: 'Otro',      value: 'Otro' }
    ];

    form = this.fb.group({
      numero: ['', Validators.required],
      fecha: [new Date(), Validators.required],

      ordenCompra: [''],
      componente: [''],
      grifo: [''],
      chofer: [''],
      
      /**
       * Añadido por mi
       */
      fuel: this.fb.group({
        fuelType:['', Validators.required],
        price: ['', Validators.required],
        gallons: ['', Validators.required],
      }),
      // combustibles: this.fb.array([
      //   this.newCombustible('Gasolina'),
      //   this.newCombustible('Petróleo'),
      //   this.newCombustible('Otro')
      // ]),

      vehiculo: this.fb.group({
        marca: [''],
        placa: ['', Validators.required],
        dependencia: [''],
        motivo: [''],
        hojaViaje: ['']
      })
    });

    // get combustibles(): FormArray<FormGroup> {
    //   return this.form.get('combustibles') as FormArray<FormGroup>;
    // }

    private newCombustible(etiqueta: string) {
      return this.fb.group({
        etiqueta: [etiqueta],
        glns: [null],
        precio: [null] // S/. total o unitario según tu flujo
      });
    }

    // onSubmit() {
    //   console.log("datos enviados: ", this.form.value);
    //   // o bien
    //   // console.log("controles:", this.form.controls);

    //   if (this.form.invalid) {
    //     this.form.markAllAsTouched();
    //     return;
    //   }
    //   console.log('Payload listo:', this.form.getRawValue());
    //   // aquí envías al backend (Laravel) como prefieras
    // }

    onSubmit() {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const v = this.form.getRawValue();

    // Mapea al backend (FuelOrderController@store)
    const payload = {
      fecha: formatDate(v.fecha!, 'yyyy-MM-dd', 'en-US'),
      numero: v.numero || null,
      orden_compra: v.ordenCompra || null,
      componente: v.componente || null,
      grifo: v.grifo || null,

      fuel_type: v.fuel!.fuelType,
      quantity_gal: v.fuel!.gallons,
      amount_soles: v.fuel!.price,

      vehiculo_marca: v.vehiculo!.marca || null,
      vehiculo_placa: v.vehiculo!.placa || null,
      vehiculo_dependencia: v.vehiculo!.dependencia || null,
      hoja_viaje: v.vehiculo!.hojaViaje || null,
      motivo: v.vehiculo!.motivo || null,

      // opcional si más adelante eliges un vehículo existente:
      // vehicle_id: someId
    };

    this.api.create(payload).subscribe({
      next: (order) => {
        this.saved.emit(order);
        // Reset con defaults
        this.form.reset({
          numero: '',
          fecha: new Date(),
          ordenCompra: '',
          componente: '',
          grifo: '',
          fuel: { fuelType: '', price: null, gallons: null },
          vehiculo: { marca: '', placa: '', dependencia: '', motivo: '', hojaViaje: '' }
        });
      }
    });
  }
}
