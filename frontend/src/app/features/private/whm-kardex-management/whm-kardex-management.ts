import { Component, OnInit, signal, effect, DestroyRef, inject, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
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
import { finalize } from 'rxjs/operators';
import { FormMovementKardex } from './interfaces/whm-kardex-management.interface';
import { OperarioOption } from './interfaces/whm-kardex-management.interface';
import { Chip } from "primeng/chip";
import { Tooltip } from 'primeng/tooltip';
import { Badge } from "primeng/badge";
import { Card } from "primeng/card";
import { OrdenCompraDetallado, OrdenCompraDetalladoRow, OrdenCompraDetalladoFilters   } from './interfaces/whm-kardex-management.interface';
import { parseHttpError } from '../../../shared/utils/parseHttpError';
import { SeeMovementsDetailsModal } from './components/see-movements-details-modal/see-movements-details-modal';

import { SelectModule } from 'primeng/select';

@Component({
  selector: 'app-whm-kardex-management',
  imports: [
    CommonModule,
    FormsModule,
    TableModule,
    Button,
    InputTextModule,
    DialogModule,
    InputNumberModule,
    IconField,
    InputIcon,
    ListboxModule,
    RadioButton,
    Toast,
    AddNewUserModal,
    Chip, Tooltip,
    Badge,
    Card,
    SeeMovementsDetailsModal,
    SelectModule
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

  window = window; 
  obras = signal<ObraLite[]>([]);
  selectedObraId: number | null = null;
  loadingPecosas = signal<boolean>(false);
  pageSize = 20;
  selectedProduct: any | null = null;
  working = false;
  message = '';

  operarios = signal<OperarioOption[]>([]);
  selectedOperarioId = signal<number | null>(null);
  showMovementModal = false;

  savingMovementLoading = signal<boolean>(false);
  listDniPeople = signal<any[]>([]);
  showAddUserModal = false;
  showMovementsDetails = false;
  ordenCompraDetalladoId = 0;
  selectedOrdenCompraDetalladoForMovements: any | null = null;
  movementsKardex = signal<any[]>([]);
  // movementsLoading = signal<boolean>(false);
  // movementsTotal = signal<number>(0);
  // expandedRowsMovements = signal<Record<number, boolean>>({});
  
  expanded = signal<boolean>(false)
  expandedRowsPecosa = signal<any>([])
  myCurrentRoles = signal([]);


  formx = signal<FormMovementKardex>({
      id_pecosa: null,      // numero PECOSA (Silucia)
      movement_type: null,
      amount: null,
      observations: null,
      people_ids: [],
  })

  pecosasx = signal({
      value: <Pecosa[]>[],
      rows: 10,
      first: 0,
      totalRecords: 0,
      rowsPerPageOptions: [10,15,20],
      loading: false,
      filters: {
        anio: '',
        numero: ''
      }
  })

  ordenCompraDetallado = signal<OrdenCompraDetallado>({
      value: [],
      rows: 10,
      first: 0,
      totalRecords: 0,
      rowsPerPageOptions: [10,15,20],
      loading: false,
      filters: {
        anio: '',
        numero: ''
      }
  })


// =====================================================================
  countries: any[] | undefined;

  selectedCountry: string | undefined;
  verData(){
    console.log(this.obras());
  }

// =====================================================================
  ngOnInit(): void {
    this.api.getObras()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (list) => {
          this.obras.set(list ?? []);
          if (this.obras().length) {
            const first = this.obras()[0];
            if(first){
              // this.getItemsPecosas(first.id, 0, this.pecosasx().rows, this.pecosasx().filters);
              this.getOrdenCompraDetallado(first.id, 0, this.ordenCompraDetallado().rows, this.ordenCompraDetallado().filters);
              this.getRolesByObra(first.id);
            }
          }
        }
      });




      this.countries = [
          { name: 'polivalente monomotor desarrollado por la compañía estadounidense General Dynamics en los años 1970 para la Fuerza Aérea de los Estados Unidos', code: 'AU' },
          { name: 'Brazil', code: 'BR' },
          { name: 'China', code: 'CN' },
          { name: 'Egypt', code: 'EG' },
          { name: 'France', code: 'FR' },
          { name: 'Germany', code: 'DE' },
          { name: 'India', code: 'IN' },
          { name: 'Japan', code: 'JP' },
          { name: 'Spain', code: 'ES' },
          { name: 'United States', code: 'US' }
      ];
  }

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



  seeDebugerData(){
    console.log(this.myCurrentRoles())
  }

  roleMeta: Record<string, { label: string; icon?: string; styleClass?: object }> = {
    'almacen.superadmin':   { label: 'Superadmin',      icon: 'pi pi-crown',     styleClass: {'background-color': '#e0e7ff', 'color': '#3730a3'}},
    'almacen.almacenero':   { label: 'Almacenero',      icon: 'pi pi-box',       styleClass: {'background-color': '#d1fae5', 'color': '#065f46'}},
    'almacen.administrador':{ label: 'Administrador',   icon: 'pi pi-shield',    styleClass: {'background-color': '#f3f4f6', 'color': '#1f2937'}},
    'almacen.residente': {label: 'Residente', icon: 'pi pi-user-edit', styleClass:           {'background-color': '#fefce8', 'color': '#854d0e'}},
    'almacen.supervisor': {label: 'Supervisor', icon: 'pi pi-eye', styleClass:               {'background-color': '#ffedd5', 'color': '#9a3412'  }},
    'almacen.operario': { label: 'Operario', icon: 'pi pi-cog', styleClass:                  {'background-color': '#e5e7eb', 'color': '#374151'  }},
  };

  humanizeRole(r: string): string {
    const last = r.split('.').pop() ?? r;
    return last.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
  }


  getOrdenCompraDetallado(obraId:number | null, first:number, rows: number, filters: OrdenCompraDetalladoFilters){
    this.selectedObraId = obraId;
    const page = Math.floor(first / rows) + 1; // 1-based 
    const perPage = rows;

    this.ordenCompraDetallado.update(objects => ({ 
      ...objects, 
      loading: true,
      rows: rows,
      first: first,
    }));

    this.api.getOrdenCompraDetallado(obraId, page, perPage, filters)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(()=>{
          this.ordenCompraDetallado.update((data)=>{return {...data, loading: false}})
          this.loadingPecosas.set(false);
        })
      ).subscribe({
          next: (response) => {
              this.ordenCompraDetallado.update((object)=>({
                ...object,
                value: response.data ?? [],
                totalRecords: response.total ?? response.data.length,
              }))
          },
        error: async (error) => {
            const p = await parseHttpError(error);
            this.messageService.add({ severity:p.severity, summary:p.title, detail:p.detail });
        }
    });
  }

  onObraChange(obraId: number) {
    // this.getItemsPecosas(obraId, 0, this.pecosasx().rows, this.pecosasx().filters);
    this.getOrdenCompraDetallado(obraId, 0, this.ordenCompraDetallado().rows, this.ordenCompraDetallado().filters);
    this.getRolesByObra(obraId);
  }

  getRolesByObra(obraId: number){
    this.api.userRolesByObra(obraId)
    .pipe(takeUntilDestroyed(this.destroyRef))
    .subscribe({
      next: (response) => {
        console.log(response);
        this.myCurrentRoles.set(response.roles)
      },
      error:  () => {}
    })
  }

  onLazyLoadPecosa(event:any){
    // this.getItemsPecosas(this.selectedObraId, event.first, event.rows, this.pecosasx().filters)
    this.getOrdenCompraDetallado(this.selectedObraId, event.first, event.rows, this.ordenCompraDetallado().filters);

  }

  onAddFilters(type:string, filter: string){
    switch (type) {
      case 'anio':
        // this.pecosasx.update((object)=>({...object, filters: {...object.filters, anio: filter}}));
        this.ordenCompraDetallado.update((object)=>({...object, filters: {...object.filters, anio: filter}}));
        break;
      case 'numero':
        // this.pecosasx.update((object)=>({...object, filters: {...object.filters, numero: filter}}));
        this.ordenCompraDetallado.update((object)=>({...object, filters: {...object.filters, numero: filter}}));
        break;
      default:
        // console.log('No reconocido');
    }
  }

  search(){
    // this.getItemsPecosas(this.selectedObraId, this.pecosasx().first, this.pecosasx().rows, this.pecosasx().filters)
    this.getOrdenCompraDetallado(this.selectedObraId, this.ordenCompraDetallado().first, this.ordenCompraDetallado().rows, this.ordenCompraDetallado().filters)
  }
  cleanFilters(){
    // this.pecosasx.update(object => ({...object, filters: {anio: '', numero: ''}}))
    this.ordenCompraDetallado.update(object => ({...object, filters: {anio: '', numero: ''}}))
    // this.getItemsPecosas(this.selectedObraId,0, this.pecosasx().rows, this.pecosasx().filters);
    this.getOrdenCompraDetallado(this.selectedObraId,0, this.ordenCompraDetallado().rows, this.ordenCompraDetallado().filters);
  }
  verdatos(){
    console.log(this.ordenCompraDetallado())
  }

  openMovementModal(row: Pecosa) {
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
  }

  onSubmitMovement() {
    if(this.savingMovementLoading()) return 
    this.savingMovementLoading.set(true);
    this.api.createKardexMovement(this.selectedObraId, this.formx()?.id_pecosa, this.formx(),  )
      .pipe(
          takeUntilDestroyed(this.destroyRef),
          finalize(()=>{this.savingMovementLoading.set(false)})
      ).subscribe({
          next: (res: any) => {
              this.closeMovementModal();
              this.toast('success', 'Operación exitosa', 'El registro se completó correctamente.');
              this.getOrdenCompraDetallado(this.selectedObraId, this.ordenCompraDetallado().first, this.ordenCompraDetallado().rows, this.ordenCompraDetallado().filters);
          },
          error: async  (error) => {
              const p = await parseHttpError(error);
              this.toast(p.severity, p.title, p.detail);
          },
      });
  }

  openMovementDetailsModal(row: Pecosa) {

    this.showMovementsDetails = true;
    this.ordenCompraDetalladoId = row.id

  }

  onSubmitMovementDetails() {
    if (!this.selectedOrdenCompraDetalladoForMovements) return;
    const idItemPecosa = Number(this.selectedOrdenCompraDetalladoForMovements.id);
    this.api.downloadPdf(this.selectedObraId, idItemPecosa)
    .pipe(
      finalize(()=>{
        // this.getItemsPecosas(this.selectedObraId, this.pecosasx().first, this.pecosasx().rows, this.pecosasx().filters)
        this.getOrdenCompraDetallado(this.selectedObraId, this.ordenCompraDetallado().first, this.ordenCompraDetallado().rows, this.ordenCompraDetallado().filters);

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

  OpenModalAddPerson(){
    this.showAddUserModal = true; 
  }
  
  closeModalAddPerson(){
     this.showAddUserModal = false; 
  }

  handleListPeopleByDni(event: any){
    const nuevaPersona = event;
    this.listDniPeople.update(lista => {
      const yaExiste = lista.some(p => p.dni === nuevaPersona.dni);
      return yaExiste ? lista : [...lista, nuevaPersona];
    });
    this.formx().people_ids = this.listDniPeople().map(p => p.dni);
    // this.formx().people_ids = [12,3,4,5];
  }

  toast(severity: 'success'|'info'|'warn'|'error', summary: string, detail: string) {
    this.messageService.add({ severity, summary, detail });
  }

  private toNum(v: any): number {
     const n = typeof v === 'number' ? v : parseFloat(String(v)); return Number.isFinite(n) ? n : 0; 
  }
  
  get totalEntradas(): number {
    return (this.movementsKardex() ?? []).reduce((s, m) => s + (m?.movement_type === 'entrada' ? this.toNum(m?.amount) : 0), 0); 
  }
  
  get totalSalidas(): number {
     return (this.movementsKardex() ?? []).reduce((s, m) => s + (m?.movement_type === 'salida' ? this.toNum(m?.amount) : 0), 0); 
  }
  
  get stockSaldo(): number {
    return this.totalEntradas - this.totalSalidas; 
  }

  downloadReport(url: string | null) {
    console.log(url);
    if (url) {
      window.open(url, '_blank');
    }
  }

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
          // this.getItemsPecosas(this.selectedObraId, 0, this.pecosasx().rows, this.pecosasx().filters);
          this.getOrdenCompraDetallado(this.selectedObraId, 0, this.ordenCompraDetallado().rows, this.ordenCompraDetallado().filters);

        }
    });
  }

  deleteReport(data: any){
    console.log(data);

      this.api.deleteReport(this.selectedObraId, data.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          console.log(response);
          this.toast('success', 'Eliminado', 'Reporte eliminado correctamente.');
          // this.getItemsPecosas(this.selectedObraId, 0, this.pecosasx().rows, this.pecosasx().filters);
          this.getOrdenCompraDetallado(this.selectedObraId, 0, this.ordenCompraDetallado().rows, this.ordenCompraDetallado().filters);


        },
        error: (err) => {
          const msg = err?.error?.message || err?.message || 'No se pudo eliminar el reporte.';
          const status = err?.status;
          const sev = status === 409 ? 'warn' : (status === 403 ? 'warn' : 'error');
          this.toast(sev as any, 'No eliminado', msg);
        }
    });
  }

  getStatusLabel(status: string): string {
    switch (status) {
      case 'in_progress': return 'en progreso';
      case 'completed': return 'completado';
      case 'cancelled': return 'cancelado';
      default: return 'indefinido';
    }
  }

  getAuthorizedSignatoryLabel(status: string): string {
    switch (status) {
      case 'almacen.almacenero': return 'Almacenero';
      case 'almacen.administrador': return 'Administrador';
      case 'almacen.residente': return 'Residente';
      case 'almacen.supervisor': return 'Supervisor';
      default: return 'indefinido';
    }
  }

  getReportSeverity(n: number): 'secondary' | 'info' | 'warn' | 'danger' {
    if (!n) return 'secondary';
    // if (n < 3) return 'info';
    // if (n < 6) return 'warn';
    return 'danger';
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

  generatePdf(e: any){
    if (!e.id) return;
    // const idItemPecosa = Number(this.selectedOrdenCompraDetalladoForMovements.id);
    this.api.downloadPdfOrdenCompra(this.selectedObraId, e.id)
    .pipe(
      finalize(()=>{
        // this.getItemsPecosas(this.selectedObraId, this.pecosasx().first, this.pecosasx().rows, this.pecosasx().filters)
        this.getOrdenCompraDetallado(this.selectedObraId, this.ordenCompraDetallado().first, this.ordenCompraDetallado().rows, this.ordenCompraDetallado().filters);

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



}
