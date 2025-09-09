// src/app/fuel-order-form/fuel-order-form.component.ts
import { Component, inject, signal } from '@angular/core';

import { CommonModule, formatDate } from '@angular/common';
import { FormArray, FormBuilder, FormGroup, FormsModule, ReactiveFormsModule, Validators } from '@angular/forms';

import { DatePickerModule } from 'primeng/datepicker';
import { InputTextModule } from 'primeng/inputtext';
import { InputNumberModule } from 'primeng/inputnumber';
import { TextareaModule } from 'primeng/textarea';
import { InputGroupModule } from 'primeng/inputgroup';
import { InputGroupAddonModule } from 'primeng/inputgroupaddon';
import { ButtonModule, Button } from 'primeng/button';
import { SelectModule } from 'primeng/select'; // 👈 nuevo
import { FuelOrderListItem  as FuelOrder } from './interfaces/fuel-vouchers.interface';
import { KardexManagementService } from '../kardex-management/services/kardex-management.service';
import { FuelVouchersService } from './services/fuel-vouchers.service';
import { TableModule } from "primeng/table";
import { IconField } from "primeng/iconfield";
import { InputIcon } from "primeng/inputicon";
import { Tag } from "primeng/tag";
import { DialogModule, Dialog } from 'primeng/dialog';       // 👈 modal
// import { NewFuelVoucher } from '../../fuel-order-form/new-fuel-voucher'; // 👈 componente del form (ver sección 3)
import { NewFuelVoucher } from './components/new-fuel-voucher/new-fuel-voucher';
@Component({
  selector: 'app-fuel-vouchers',
    imports: [
    FormsModule,
    CommonModule, ReactiveFormsModule,
    DatePickerModule, InputTextModule, InputNumberModule,
    TextareaModule, InputGroupModule, InputGroupAddonModule,
    ButtonModule, SelectModule,
    TableModule,
    IconField,
    InputIcon,
    Tag, NewFuelVoucher,
    Dialog
],
  templateUrl: './fuel-vouchers.html',
  styleUrl: './fuel-vouchers.css'
})
export class FuelVouchers {
  constructor(private api:FuelVouchersService ){
    // effect(()=>{
    //   console.log("valor inicial del filterProducts: ", this.filterProducts());
    // })
  }
// imports orientativos:
// import { signal } from '@angular/core';
// import { TableLazyLoadEvent } from 'primeng/table';
// import { FuelOrdersService, FuelOrder } from 'src/app/core/services/fuel-orders.service';

pageSize = 15;

orders = signal<FuelOrder[]>([]);
ordersTotal = signal(0);
loadingOrders = signal(false);
selectedOrder?: FuelOrder | null;

uiFilters = { numero: '', placa: '', all: false };
showNewModal = false;

ngOnInit() {
  this.load(1, this.pageSize);
}

onOrdersLazyLoad(e: any /* TableLazyLoadEvent */) {
  const first = e?.first ?? 0;
  const rows  = e?.rows ?? this.pageSize;
  const page  = first / rows + 1;
  this.load(page, rows);
}

onSearchClick() {
  this.load(1, this.pageSize);
}

onClearClick() {
  this.uiFilters = { numero: '', placa: '', all: false };
  this.load(1, this.pageSize);
}

openNewModal() { this.showNewModal = true; }
closeNewModal() { this.showNewModal = false; }
// cuando el hijo guarda
onCreated(_order: any) {
  this.closeNewModal();
  this.load(); // refresca la lista
}

private load(page = 1, rows = this.pageSize) {
  this.loadingOrders.set(true);

  this.api.list({
    numero: this.uiFilters.numero || undefined,
    placa:  this.uiFilters.placa  || undefined,
    all:    this.uiFilters.all,
    page
  }).subscribe({
    next: (res) => {
      this.orders.set(res.data);
      this.ordersTotal.set(res.total);
      this.loadingOrders.set(false);
    },
    error: () => this.loadingOrders.set(false)
  });
}

/** Lógica mínima para mostrar/ocultar botones de aprobar/rechazar según estado y rol actual */
// canApprove(order: FuelOrder): boolean {
//   // Ejemplo simple de UI: el backend es la autoridad final.
//   // - Si eres supervisor/inspector: solo cuando supervisor_status == null
//   // - Si eres jefe: solo cuando supervisor_status == 'approved' y manager_status == null
//   // Puedes inyectar AuthService para saber el rol actual y evaluarlo aquí.
//   const role = this.auth.currentRole(); // implementa según tu app
//   if ((role === 'supervisor' || role === 'inspector') && order.supervisor_status == null) return true;
//   if (role === 'jefe' && order.supervisor_status === 'approved' && order.manager_status == null) return true;
//   return false;
// }

openOrderDetails(order: FuelOrder) {
  // Abre modal o navega a detalle
}

// onDecision(order: FuelOrder, decision: 'approved'|'rejected') {
//   // Llama a PATCH /api/fuel-orders/{id}/decision
//   this.api.decision(order.id, { decision }).subscribe(() => {
//     this.load(); // refresca
//   });
// }
}
