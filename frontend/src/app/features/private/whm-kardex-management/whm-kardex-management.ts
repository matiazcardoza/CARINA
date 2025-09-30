import { Component, OnInit, signal, effect, DestroyRef, inject, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

/* PrimeNG (standalone) */
import { TableModule } from 'primeng/table';
import { Button } from 'primeng/button';
import { InputTextModule } from 'primeng/inputtext';
import { DialogModule } from 'primeng/dialog';
import { InputNumberModule } from 'primeng/inputnumber';
import { IconField } from 'primeng/iconfield';
import { InputIcon } from 'primeng/inputicon';
import { ListboxModule } from 'primeng/listbox';
import { RadioButton } from 'primeng/radiobutton';
import { Toast } from 'primeng/toast';
import { MessageService } from 'primeng/api';
import { AddNewUserModal } from './components/add-new-user-modal/add-new-user-modal';
import { WhmKardexManagementService, ObraLite, MovementsPage } from './services/whm-kardex-management.service';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { Report, SignatureParams } from './interfaces/whm-kardex-management.interface';
import { ApiResponseOperarios } from './interfaces/whm-kardex-management.interface';
import { FiltersPecosas } from './interfaces/whm-kardex-management.interface';
import { PecosaResponse } from './interfaces/whm-kardex-management.interface';
import { Pecosa } from './interfaces/whm-kardex-management.interface';
// import { finalize } from 'rxjs';
import { finalize } from 'rxjs/operators';
import { FormMovementKardex } from './interfaces/whm-kardex-management.interface';
import { OperarioOption } from './interfaces/whm-kardex-management.interface';

@Component({
  selector: 'app-whm-kardex-management',
  imports: [
    CommonModule, FormsModule,
    TableModule, Button, InputTextModule, DialogModule, InputNumberModule,
    IconField, InputIcon, ListboxModule, RadioButton, Toast,
    AddNewUserModal
  ],
  providers: [MessageService],
  templateUrl: './whm-kardex-management.html',
  styleUrl: './whm-kardex-management.css'
})
export class WhmKardexManagement implements OnInit {
  private api = inject(WhmKardexManagementService);
  private readonly messageService = inject(MessageService);
  private readonly destroyRef = inject(DestroyRef);

  constructor(){
    let prev: 'entrada'|'salida'|null = null;
    effect(() => {
      const curr = this.formx().movement_type; // se reejeuta por cualquier cambio del form
      if (curr === 'salida' && curr !== prev) {
        this.getUsersOfMovementKardex();  // sólo cuando cambia a 'salida'
      }
      prev = curr;
    });
  }

  window = window; // Hace que 'window' esté disponible en el template
  obras = signal<ObraLite[]>([]);
  selectedObraId: number | null = null;
  pecosas = signal<Pecosa[]>([]);
  loadingPecosas = signal<boolean>(false);
  productsTotal = signal<number>(0);
  pageSize = 20;
  selectedProduct: any | null = null;
  // uiFilters: { numero?: string; anio?: number | undefined; item?: string; desmeta?: string } = {
  //   numero: '',
  //   anio: undefined,
  //   item: '',
  //   desmeta: '',
  // };
  operarios = signal<OperarioOption[]>([]);
  selectedOperarioId = signal<number | null>(null);
  showMovementModal = false;
  showMovementDetailsModal = false;
  movementOptionsStr: Array<'entrada' | 'salida'> = ['entrada', 'salida'];
  form = {
    id_pecosa_silucia: null as string | null,      // numero PECOSA (Silucia)
    id_item_pecosa_silucia: null as number | null, // idsalidadet (Silucia)
    movement_type: null as 'entrada' | 'salida' | null,
    amount: null as number | null,
    observations: null as string | null,
    people_dnis: [] as string[],
    silucia_pecosa: null as any
  };

  formx = signal<FormMovementKardex>({
    id_pecosa: null,      // numero PECOSA (Silucia)
    movement_type: null,
    amount: null,
    observations: null,
    // people_dnis: [],
    people_ids: [],
  })

  setMovementType(type:string, data: 'entrada'|'salida'|number|null|string){
    switch (type) {
      case 'entrada':
        this.formx.update((object)=>({...object, movement_type: data as 'entrada'|'salida'}));
        break;
      case 'salida':
        this.formx.update((object)=>({...object, movement_type: data as 'entrada'|'salida'}));
        break;
      case 'observations':
        this.formx.update((object)=>({...object, observations: (data as string) ?? null}));
        break;
      case 'cantidad':
        this.formx.update((object)=>({...object, amount: (data as number) ?? null}));
        break;
      default:
        // console.log('No reconocido');
    }
  }

  getUsersOfMovementKardex(){
    console.log("peticion para obtener usuarios");
      this.api.getOperarios(this.selectedObraId)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response:ApiResponseOperarios) => {
          const options = (response?.data ?? [])
              .filter(u => !!u.persona)
              .map(u => ({
                id: u.id,
                num_doc: u.persona!.num_doc,
                label: `${u.persona!.num_doc} ${u.persona!.name} ${u.persona!.last_name}`,
              }))
          this.operarios.set(options);
          console.log(response);
        },
        error: () => {
            // this.loadingPecosas.set(false);
            // this.pecosasx.update(objects => ({...objects, loading: false})) 
        }
    });
  }

  onOperarioChange(id: number | null) {
    this.selectedOperarioId.set(id);
    this.formx.update(object => ({
      ...object,
      people_ids: id? [id] : []
    }));
  }

  verdatosdemovementkardex(){
    console.log(this.formx())
  }

  savingMovementLoading = signal<boolean>(false);
  listDniPeople = signal<any[]>([]);
  showAddUserModal = false;
  selectedPecosaForMovements: any | null = null;
  movementsKardex = signal<any[]>([]);
  movementsLoading = signal<boolean>(false);
  movementsTotal = signal<number>(0);
  movementsPageSize = 50;
  expandedRowsMovements = signal<Record<number, boolean>>({});
  lastProductsPage = 1;
  lastProductsRows = this.pageSize;
  lastSelectedKey: number | null = null;
  expanded = signal<boolean>(false)
  expandedRowsPecosa = signal<any>([])

  pecosasx = signal({
    value: <Pecosa[]>[],
    rows: 5,
    first: 0,
    totalRecords: 0,
    rowsPerPageOptions: [5,10,15,20],
    loading: false,
    filters: {
      anio: '',
      numero: ''
    }
  })

  ngOnInit(): void {
    this.api.getObras()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (list) => {
          this.obras.set(list ?? []);
          if (this.obras().length) {
            const first = this.obras()[0];
            if(first){
              this.getItemsPecosas(first.id, 0, this.pecosasx().rows, this.pecosasx().filters)
            }
          }
        }
      });
  }

  getItemsPecosas(obraId:number | null, first:number, rows: number, filters: FiltersPecosas){
    this.selectedObraId = obraId;
    const page = Math.floor(first / rows) + 1; // 1-based 
    const perPage = rows;

    this.pecosasx.update(objects => ({ 
      ...objects, 
      loading: true,
      rows: rows,
      first: first,
    }));

    this.api.getItemPecosas(obraId, page, perPage, filters)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
          next: (response) => {

              this.pecosasx.update((object)=>({
                ...object,
                value: response.data ?? [],
                totalRecords: response.total ?? response.data.length,
                loading: false
              }))
              // ---------------------------------------
              // this.pecosas.set(response.data ?? []);
              // this.productsTotal.set(response.total ?? response.data?.length ?? 0);
              // this.pageSize = response.per_page ?? this.pageSize;
              // this.loadingPecosas.set(false);

              // if (this.lastSelectedKey != null) {
              //     const match = (response.data ?? []).find((r: any) => String(r.idsalidadet_silucia) === String(this.lastSelectedKey));
              //     this.selectedProduct = match || null;
              // }
              // -------------------------------
          },
        error: () => {
            this.loadingPecosas.set(false);
            this.pecosasx.update(objects => ({...objects, loading: false})) 
        }
    });
  }

  onObraChange(obraId: number) {
    // this.selectedObraId = obraId;
    // this.loadFirstPage();
    this.getItemsPecosas(obraId, 0, this.pecosasx().rows, this.pecosasx().filters)
  }

  onLazyLoadPecosa(event:any){
    this.getItemsPecosas(this.selectedObraId, event.first, event.rows, this.pecosasx().filters)
  }

  onAddFilters(type:string, filter: string){
    switch (type) {
      case 'anio':
        this.pecosasx.update((object)=>({...object, filters: {...object.filters, anio: filter}}));
        break;
      case 'numero':
        this.pecosasx.update((object)=>({...object, filters: {...object.filters, numero: filter}}));
        break;
      default:
        // console.log('No reconocido');
    }
  }

  search(){
    this.getItemsPecosas(this.selectedObraId, this.pecosasx().first, this.pecosasx().rows, this.pecosasx().filters)
  }
  cleanFilters(){
    this.pecosasx.update(object => ({...object, filters: {anio: '', numero: ''}}))
    this.getItemsPecosas(this.selectedObraId,0, this.pecosasx().rows, this.pecosasx().filters);
  }
  verdatos(){
    console.log(this.pecosasx())
  }

  openMovementModal(row: Pecosa) {
    // this.form.id_pecosa_silucia = row.numero;
    // this.form.id_item_pecosa_silucia = Number(row.idsalidadet_silucia);
    // this.form.silucia_pecosa = JSON.parse(JSON.stringify(row));
    this.formx.update((object)=>({
      ...object,
      id_pecosa: row.id
    }));

    this.showMovementModal = true;
  }

  closeMovementModal() {
    this.formx.set({
      id_pecosa: null,      // numero PECOSA (Silucia)
      movement_type: null,
      amount: null,
      observations: null,
      // people_dnis: [],
      people_ids: [],
    })
    this.showMovementModal = false;
    // this.form = {
    //   silucia_pecosa: null,
    //   movement_type: null,
    //   amount: null,
    //   id_pecosa_silucia: null,
    //   id_item_pecosa_silucia: null,
    //   observations: null,
    //   people_dnis: [],
    // };
    // this.listDniPeople.set([]);
  }

  onSubmitMovement() {
    console.log("cerramos modal");
    // this.lastSelectedKey = this.selectedProduct?.idsalidadet_silucia ?? null;
    // const page = this.lastProductsPage;
    // const perPage = this.lastProductsRows;

    this.savingMovementLoading.set(true);
    this.api.createKardexMovement(this.selectedObraId, this.formx()?.id_pecosa, this.formx(),  )
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (res: any) => {
          if (res?.ok === false) {
            this.toast('error', 'Ocurrió un error', 'Por favor vuelva a intentarlo');
            return;
          }
          this.closeMovementModal();
          this.toast('success', 'Operación exitosa', 'El registro se completó correctamente.');
          this.getItemsPecosas(this.selectedObraId, this.pecosasx().first, this.pecosasx().rows, this.pecosasx().filters)
        },
        error: _ => this.toast('error', 'Error', 'Por favor vuelva a intentarlo'),
        complete: () => this.savingMovementLoading.set(false)
      });
  }

  openMovementDetailsModal(row: Pecosa) {
    this.selectedPecosaForMovements = row;
    this.showMovementDetailsModal = true;
    this.expandedRowsMovements.set({});
    this.fetchMovements(1, this.movementsPageSize);
  }

  private fetchMovements(page: number, perPage: number) {
    if (!this.selectedPecosaForMovements) return;
    // const orderNum = this.selectedPecosaForMovements.numero;
    const itemPecosaId = Number(this.selectedPecosaForMovements.id);
    
    this.movementsLoading.set(true);
    this.api.getKardexMovement(this.selectedObraId, itemPecosaId, { page, per_page: perPage })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (res: MovementsPage) => {
          this.movementsKardex.set(res.movements.data);
          this.movementsTotal.set(res.movements.total);
          this.movementsPageSize = res.movements.per_page;
          this.movementsLoading.set(false);
        },
        error: _ => this.movementsLoading.set(false)
      });
  }

  onMovementsLazyLoad(e: any) {
    const page = Math.floor((e.first ?? 0) / (e.rows ?? this.movementsPageSize)) + 1;
    const perPage = e.rows ?? this.movementsPageSize;
    this.expandedRowsMovements.set({});
    this.fetchMovements(page, perPage);
  }

  closeMovementDetailsModal(){ this.showMovementDetailsModal = false; this.movementsKardex.set([]); }

  onSubmitMovementDetails() {
    if (!this.selectedPecosaForMovements) return;
    const idItemPecosa = Number(this.selectedPecosaForMovements.id);
    this.api.downloadPdf(this.selectedObraId, idItemPecosa)
    .pipe(
      finalize(()=>{
        this.getItemsPecosas(this.selectedObraId, this.pecosasx().first, this.pecosasx().rows, this.pecosasx().filters)
      })
    )
    .subscribe(res => {
      const blob = res.body!;
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = '';
      document.body.appendChild(a); a.click(); a.remove();
      setTimeout(()=>{URL.revokeObjectURL(url)}, 1000);
    });

  }

  OpenModalAddPerson(){ this.showAddUserModal = true; }
  
  closeModalAddPerson(){ this.showAddUserModal = false; }

  handleListPeopleByDni(event: any){
    const nuevaPersona = event;
    this.listDniPeople.update(lista => {
      const yaExiste = lista.some(p => p.dni === nuevaPersona.dni);
      return yaExiste ? lista : [...lista, nuevaPersona];
    });
    this.form.people_dnis = this.listDniPeople().map(p => p.dni);
  }

  onRowExpandMovement(e: any) {
    const id = e.data?.id; if (id == null) return;
    this.expandedRowsMovements.update(map => ({ ...map, [id]: true }));
  }

  onRowCollapseMovement(e: any) {
    const id = e.data?.id; if (id == null) return;
    this.expandedRowsMovements.update(map => {
      const { [id]: _omit, ...rest } = map; return rest;
    });
  }

  toast(severity: 'success'|'info'|'warn'|'error', summary: string, detail: string) {
    this.messageService.add({ severity, summary, detail });
  }

  private toNum(v: any): number { const n = typeof v === 'number' ? v : parseFloat(String(v)); return Number.isFinite(n) ? n : 0; }
  
  get totalEntradas(): number { return (this.movementsKardex() ?? []).reduce((s, m) => s + (m?.movement_type === 'entrada' ? this.toNum(m?.amount) : 0), 0); }
  
  get totalSalidas(): number { return (this.movementsKardex() ?? []).reduce((s, m) => s + (m?.movement_type === 'salida' ? this.toNum(m?.amount) : 0), 0); }
  
  get stockSaldo(): number { return this.totalEntradas - this.totalSalidas; }

  /**
   * Funcionalidades para expandir y contraer filas
   */
  // onRowExpandPecosa(data:any){
  // }
  // onRowCollapsePecosa(data: any){
  // }
  downloadReport(url: string | null) {
    console.log(url);
    if (url) {
      window.open(url, '_blank');
    }
  }

  working = false;
  message = '';
  signReport(url: Report | null) {

    console.log("datos de peticion r: ", url);
    // r.download_url = 'http://127.0.0.1:8000/api/payments/PDF_NUMERO_1.pdf';
      // 1) Guards básicos
    if (!url) return;
    if (!url.pdf_path) {
      this.message = 'No hay URL de descarga del PDF.';
      return;
    }
    // url que recibirá el pdf firmado
    if (!url.pdf_path) {
      // Cuando no te toca, el backend puede devolver null. Igual mostramos por qué:
      this.message = 'No hay callback para este usuario o paso. Verifica si te toca firmar.';
      return;
    }
    if (url.current_step.status != "pending") {
      this.message = 'Aún no es tu turno de firma (botón deshabilitado).';
      return;
    }

    // const callback = `${this.environment.API_URL}api/signatures/callback` + `?flow_id=${flowId}&step_id=${stepId}&token=${callbackToken}`;
    this.working = true;
    this.message = '';

    // 2) Armar parámetros para el proveedor
    const params: SignatureParams = {
      // direccion url que el firmador usara para recoger el pdf
      location_url_pdf: url.pdf_path,      
      // dirección url que el firmador usara para devolver el pdf, en esta url incluimos: report_id, step_id, user_id, rol y token ---> por ejemplo: "sign_callback_url": "http://127.0.0.1:8000/api/signatures/callback?report_id=11&step_id=41&token=cH4j0I9vI6XZ9a3VYZUp7qbCVIf0kTgAaCPyzghffW9hN8mv"
      post_location_upload: url.sign_callback_url, 
      rol: url.current_step.role ?? 'firmante',
      tipo: 'recursos',
      visible_position: false,
      bacht_operation: false,
      npaginas: url?.current_step.page ,
      posx: url?.current_step?.pos_x,
      posy: url?.current_step?.pos_y,
      // token: url.current_step.callback_token
      token: ''
    };

    // 3) Abrir el firmador
    this.working = true;
    this.message = '';


    this.api.openSignatureWindow$(params).subscribe({
        next: () => {
          this.message = 'Firma completada y recibida por el backend.';
          // 4) Refrescar la página/tabla para que cambie el estado (current_step, can_sign, etc.)
          // const base = this.lastLazyLoadEventOrDefault();
          // this.onProductsLazyLoad({ ...base });
        },
        error: (err) => {
          this.message = `${err?.message || 'Error de firma'}`;
        },
        complete: () => {
          this.working = false;
        }
    });
  }

  openExternal(url?: string | null) {
    if (!url) return;
    // Abrir en nueva pestaña/ventana
    window.open(url, '_blank');
  }

  goTo(url?: string | null) {
    if (!url) return;
    // Navegar en la misma pestaña
    window.location.href = url;
  }

  onRowExpandPecosa(e: any) {
    const id = e.data?.id;
    if (id == null) return;
    this.expandedRowsPecosa.update(map => ({ ...map, [id]: true }));
  }

  onRowCollapsePecosa(e: any) {
    const id = e.data?.id;
    if (id == null) return;
    this.expandedRowsPecosa.update(map => {
      const { [id]: _omit, ...rest } = map;
      return rest;
    });
  }

  /**
   * funciones del modal para adicionar movimientos kardex
   */

}
