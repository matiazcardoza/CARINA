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
import { TooltipModule } from 'primeng/tooltip';
// import { NewFuelVoucher } from '../../fuel-order-form/new-fuel-voucher'; // 👈 componente del form (ver sección 3)
import { NewFuelVoucher } from './components/new-fuel-voucher/new-fuel-voucher';
import { ChangeDetectorRef } from '@angular/core';
import { SignatureParams } from '../../../shared/draft/digital-signature/interface/signature-params.interface';
import { DigitalSignatureService } from '../../../shared/draft/digital-signature/services/digital-signature.service';
import { environment } from '../../../../environments/environment';
type SignStep = {
  id: number; order: number; role: string;
  status: 'pending'|'signed'|'rejected';
  page?: number; pos_x?: number; pos_y?: number; width?: number; height?: number;
  callback_token?: string;
};

type Flow = { id:number; current_step:number; status:string; steps:SignStep[]; };
type ReportDTO = {
  id:number; status:string; category:string;
  pdf_path:string; pdf_page_number:number;
  download_url?: string;
  flow: Flow;
  user_step?: SignStep;        // si backend lo incluye
  can_sign?: boolean;          // si backend lo incluye
  current_role?: string;       // si backend lo incluye
};


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
    Dialog,
    TooltipModule    
],
  templateUrl: './fuel-vouchers.html',
  styleUrl: './fuel-vouchers.css'
})
export class FuelVouchers {
  // constructor(private api:FuelVouchersService ){
  //   // effect(()=>{
  //   //   console.log("valor inicial del filterProducts: ", this.filterProducts());
  //   // })
  // }
  constructor(private api: FuelVouchersService, private cdr: ChangeDetectorRef, private signature: DigitalSignatureService) {}
// imports orientativos:
// import { signal } from '@angular/core';
// import { TableLazyLoadEvent } from 'primeng/table';
// import { FuelOrdersService, FuelOrder } from 'src/app/core/services/fuel-orders.service';

pageSize = 15;

orders = signal<FuelOrder[]>([]);
ordersTotal = signal(0);
loadingOrders = signal(false);
selectedOrder?: FuelOrder | null;

uiFilters = signal({ numero: '', placa: '', all: false });
showNewModal = false;
showSignModal = false;
signing: { orderId: number, data?: ReportDTO } | null = null;

// ngOnInit() {
//   this.load(1, this.pageSize);
// }



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
  this.uiFilters.set({ numero: '', placa: '', all: false });
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
const filters = this.uiFilters(); 
  this.api.list({
    numero: filters.numero || undefined,
    placa:  filters.placa  || undefined,
    all:    filters.all,
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

onFilterChange(field: 'numero' | 'placa' | 'all', event: Event) {
  const input = event.target as HTMLInputElement;
  const value = field === 'all' ? input.checked : input.value;

  this.uiFilters.update(currentFilters => ({
    ...currentFilters,
    [field]: value
  }));
}

onAllFilterChange(isChecked: boolean) {
  this.uiFilters.update(currentFilters => ({
    ...currentFilters,
    all: isChecked
  }));
}
// DENTRO DE TU CLASE FuelVouchers

get currentStepRole(): string {
  // Accede a la misma data que usa el template
  const info = this.signing?.data;

  // Si no hay información, devuelve el valor por defecto
  if (!info?.flow?.steps || !info.flow.current_step) {
    return '—';
  }

  // Encuentra el paso actual
  const currentStep = info.flow.steps.find(s => s.order === info.flow.current_step);

  // Devuelve el rol del paso o el valor por defecto
  return currentStep?.role || '—';
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


  generateReport(order: FuelOrder) {
      // Descarga y crea flujo en backend; luego refresca la lista
      this.loadingOrders.set(true);
      this.api.generateReport(order.id).subscribe({
        next: (blob) => {
          // abre el PDF generado (opcional)
          const url = URL.createObjectURL(blob);
          window.open(url, '_blank');
          setTimeout(() => URL.revokeObjectURL(url), 10000);
          this.load(); // refresca para que aparezca el estado
        },
        error: () => this.loadingOrders.set(false),
        complete: () => this.loadingOrders.set(false),
      });
  }

  // BOTÓN: abre modal de firmas
  openSignModalx(order: FuelOrder) {
    this.signing = { orderId: order.id, data: undefined };
    this.showSignModal = true;

    // trae estado del reporte/flujo para este vale
    this.api.getSignatureStatus(order.id).subscribe({
      next: (res) => this.signing = { orderId: order.id, data: res },
      error: () => { /* opcional: toast */ },
    });
  }

  openSignModal(order: FuelOrder) {
    console.log("vale de transporte seleccionado: ", order);
    this.signing = { orderId: order.id, data: undefined };
    this.showSignModal = true;

    // A veces, forzar la detección de cambios antes de la llamada asíncrona ayuda
    // this.cdr.detectChanges(); 

    this.api.getSignatureStatus(order.id).subscribe({
      next: (res) => { this.signing = { orderId: order.id, data: res }
        // 3. Posponer la actualización al siguiente ciclo
        // setTimeout(() => {
        //   ;
        // }, 0);
      },
      error: () => { /* opcional: toast */ },
    });
  }
 // dentro de la clase
  working = false;
  message = '';

signNow() {
  const info = this.signing?.data as any;
  if (!info) return;

  const step =
    info.user_step ??
    info.flow?.steps?.find((s: any) => Number(s.order) === Number(info.flow?.current_step));

  if (!step) { this.message = 'No hay paso de firma activo.'; return; }
  if (!info.download_url) { this.message = 'No hay URL de descarga del PDF.'; return; }
  if (!info.can_sign)     { this.message = 'Aún no es tu turno para firmar.'; return; }

  // callback del backend que recibirá el PDF firmado
  const callback =
    `http://localhost:8000/api/signatures/callback` +
    `?flow_id=${info.flow.id}&step_id=${step.id}&token=${step.callback_token}`;

  // Normaliza numéricos (por si llegan como string)
  const page  = Number(step.page  ?? 1);
  const pos_x = Number(step.pos_x ?? 0);
  const pos_y = Number(step.pos_y ?? 0);

  const params: SignatureParams = {
    location_url_pdf:     info.download_url,
    post_location_upload: callback,
    rol:    info.current_role ?? 'firmante',
    tipo:   'fuel_order',
    visible_position: false,
    bacht_operation: false,
    npaginas: page,
    posx:    pos_x,
    posy:    pos_y,
    token:   ''
  };

  this.working = true;
  this.message = '';

  // Debe coincidir con el origin que valida openSignatureWindow$
  const FIRMA_API = 'https://sistemas.regionpuno.gob.pe/firma-api/';
  console.log("datos del parametro: ", params)
  this.signature.openSignatureWindow$(params, FIRMA_API).subscribe({
    next: () => {
      this.message = '✅ Firma completada y recibida por el backend.';
      // refresca estado en el modal y la tabla
      this.api.getSignatureStatus(this.signing!.orderId).subscribe({
        next: (res) => this.signing = { orderId: this.signing!.orderId, data: res },
        complete: () => { this.working = false; }
      });
      this.load();
    },
    error: (err) => {
      // Tip común: si el popup es bloqueado por el navegador, verás este mensaje
      this.message = `❌ ${err?.message || 'Error de firma'}`;
      this.working = false;
    }
  });
}

  viewReport(order: FuelOrder) {
    // si la API que usas en list() ya trae download_url dentro de order.report, úsalo:
    const url = (order as any)?.report?.download_url;
    if (url) { window.open(url, '_blank'); return; }

    // fallback: consulta detalle y usa su download_url
    this.api.getSignatureStatus(order.id).subscribe({
      next: (res) => {
        if (res.download_url) window.open(res.download_url, '_blank');
      }
    });
  }

  // Helpers UI para habilitar/deshabilitar botones
  hasReport(order: FuelOrder) {
    return !!(order as any)?.report;
  }
  canGenerate(order: FuelOrder) {
    // permite generar si no hay reporte o si el estado no está “in_progress”
    const rep = (order as any)?.report as ReportDTO | undefined;
    return !rep || rep.status !== 'in_progress';
  }
  canOpenSign(order: FuelOrder) {
    return this.hasReport(order);
  }
  canView(order: FuelOrder) {
    const rep = (order as any)?.report as ReportDTO | undefined;
    return !!rep?.download_url || this.hasReport(order);
  }
}
