import { Component, DestroyRef, inject, Signal, signal, effect  } from '@angular/core';
import { SlicePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient, HttpEvent } from '@angular/common/http'; // <-- HttpEvent

// PrimeNG (standalone components / modules necesarios)
import { TableModule } from 'primeng/table';
import { InputTextModule } from 'primeng/inputtext';
import { DialogModule } from 'primeng/dialog';
import { InputNumberModule } from 'primeng/inputnumber';
import { AutoComplete } from 'primeng/autocomplete';
import { Button } from 'primeng/button';
import { Tag } from 'primeng/tag';
import { IconField } from 'primeng/iconfield';
import { InputIcon } from 'primeng/inputicon';
import { KardexManagementService } from './services/kardex-management.service';
// Data mock
import { clients, products } from './utils/mockup-data';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { AddNewUserModal } from './components/add-new-user-modal/add-new-user-modal';
import { ListboxModule } from 'primeng/listbox';
import { DigitalSignatureService } from '../../../shared/draft/digital-signature/services/digital-signature.service';
import { SignatureParams } from '../../../shared/draft/digital-signature/interface/signature-params.interface';
import { catchError, EMPTY, finalize, Observable, of, switchMap, tap } from 'rxjs';
import { Toast } from 'primeng/toast';
import { MessageService } from 'primeng/api';
import { Ripple } from 'primeng/ripple';
import { filter } from './interfaces/kardex-management.interface';
import { RadioButton } from "primeng/radiobutton";
@Component({
  selector: 'app-kardex-management',
  standalone: true,
  imports: [Toast,
    // Ripple,
    // Angular
    FormsModule,
    // SlicePipe,
    // PrimeNG
    TableModule, InputTextModule, DialogModule, InputNumberModule,
    AutoComplete, Button,
    // Tag, 
    IconField, InputIcon, AddNewUserModal, ListboxModule, RadioButton],
  providers:[MessageService],
  templateUrl: './kardex-management.html',
  styleUrl: './kardex-management.css'
})

export class KardexManagement {
  // ----- State (signals / props) -----
  customers = signal<any[]>([]);
  pecosas = signal<any[]>([]);
  loadingPecosas = signal<boolean>(false);
  errorLoadingProducts = signal<string>('');
  selectedCustomers!: any;
  selectedProduct: any | null = null;
  filterProducts =  signal<string>('');
  listDniPeople = signal<any[]>([]);
  savingMovementLoading = signal<boolean>(false);;
  // expandedRowsMovements: { [id: number]: boolean } = {};
  expandedRowsMovements = signal<Record<number, boolean>>({});
  uiFilters = {
    numero: '',
    anio: undefined as number | undefined,
    item: '',
    desmeta: '',
    siaf: '',
    ruc: '',
    rsocial: '',
    email: ''
  };

  // Modales
  openModalSeeDetailsOfMovimentKardex = signal<boolean>(true);
  openModaladdMovimentKardex = signal<boolean>(true);
  showMovementModal = false;
  showMovementDetailsModal = false;
  showAddUserModal = false
  // Personas (opcional, listo para crecer)
  people = signal<any[]>([]);
  selectedPersonId: number | null = null;
  openAddPerson = signal(false);
  newPersonName = '';
  newPersonDocument = '';

  // Form movimiento (usaremos AutoComplete tipo dropdown con strings “Entrada/Salida”)
  movementOptionsStr: Array<'entrada' | 'salida'> = ['entrada', 'salida'];
  filteredMovementOptions: string[] = [];

  form = {

    // id_container_silucia: null as string | null,
    id_pecosa_silucia: null as string | null,
    id_item_pecosa_silucia: null as number | null,
    movement_type: null as 'entrada' | 'salida' | null,
    amount: null as number | null,
    observations: null as string |null,
    people_dnis: [] as string[],
    silucia_pecosa: null as any
  };

  movementsKardex    = signal<any[]>([]);
  movementsLoading   = signal<boolean>(false);
  movementsTotal     = signal<number>(0);
  movementsPageSize  = 50; // lo ajustaremos con lo que devuelva el backend
  selectedPecosaForMovements: any | null = null;

  private readonly destroyRef = inject(DestroyRef); 
  constructor(private service:KardexManagementService, private signature: DigitalSignatureService, private messageService: MessageService){
    effect(()=>{
      console.log("valor inicial del filterProducts: ", this.filterProducts());
    })
  }

  // ----- Lifecycle -----
  productsTotal = signal<number>(0);
  pageSize = 20; 

  onSearchClick() {
    // Normaliza "anio" (por si el input devuelve string vacío)
    const raw = (this.uiFilters.anio as any);
    const anio = raw === '' || raw == null ? undefined : Number(raw);

    this.getProductsOfSiluciaBackend({
      ...this.uiFilters,
      anio,
      page: 1,
      per_page: this.pageSize
    });
  }

  onClearClick() {
    this.uiFilters = {
      numero: '',
      anio: undefined,
      item: '',
      desmeta: '',
      siaf: '',
      ruc: '',
      rsocial: '',
      email: ''
    };

    this.getProductsOfSiluciaBackend({
      page: 1,
      per_page: this.pageSize
    });
  }

  ngOnInit() {
    // this.getProductsOfSiluciaBackend({});
    this.loadFirstPage();
  }
  private loadFirstPage() {
    this.getProductsOfSiluciaBackend({ ...this.uiFilters, page: 1, per_page: this.pageSize });
  }

  getProductsOfSiluciaBackend(filters:filter){
    this.loadingPecosas.set(true);
    // this.service.getSiluciaProducts(filters)
    this.service.getSiluciaPecosas(filters)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (res) => {
          console.log("respuesta (pecosas): ", res);
          this.pecosas.set(res.data);
          this.productsTotal.set(res.total ?? res.data?.length ?? 0);
          this.pageSize = res.per_page ?? this.pageSize;
          this.loadingPecosas.set(false);
          // Restaura selección si el producto está en la página actual
          if (this.lastSelectedKey != null) {
            const match = (res.data ?? []).find((r: any) => r.idcompradet === this.lastSelectedKey);
            this.selectedProduct = match || null;
          }
        },
        error: _ => { this.errorLoadingProducts.set('No se pudo cargar'); this.loadingPecosas.set(false); }
      });
  }

  lastProductsPage = 1;
  lastProductsRows = this.pageSize;
  lastSelectedKey: number | null = null; // idcompradet del producto seleccionado
  onProductsLazyLoad(e: any) {
    const first = e.first ?? 0;
    const rows  = e.rows ?? this.pageSize;
    const page  = Math.floor(first / rows) + 1;

    this.lastProductsPage = page;
    this.lastProductsRows = rows;

    this.getProductsOfSiluciaBackend({ ...this.uiFilters, page, per_page: rows });
  }
  // ----- Helpers UI -----
  getSeverity(status: string) {
    switch (status) {
      case 'unqualified': return 'danger';
      case 'qualified':   return 'success';
      case 'new':         return 'info';
      case 'negotiation': return 'warn';
      case 'renewal':     return null;
      default:            return 'unknown';
    }
  }

  // ----- Table actions -----
  handleModalSeeDetailsOfMovimentKardex() {
    this.openModalSeeDetailsOfMovimentKardex.update(v => !v);
  }

  // ----- Personas -----
  guardarNuevaPersona() {
    const name = this.newPersonName?.trim();
    if (!name) return;
    const newId = Date.now();
    const newPerson = { id: newId, name, document: this.newPersonDocument?.trim() };
    this.people.update(list => [...list, newPerson]);
    this.selectedPersonId = newId;
    this.newPersonName = '';
    this.newPersonDocument = '';
    this.openAddPerson.set(false);
  }



  // ----- Movimiento (modal) -----
  openMovementModal(_row?: any) {

    /**
     * Los valores con los cuales identificaremos a un producto es por su id unico 
     * de proudcto llamado "idompradet"y tambien necesitamos indentificar a la orden, 
     * lo hacemo por el id de orden que es "numero", este valor tiene que ser 
     * unico en la base de datos de silucia.
     * 
     * posteriormente vamos a necesitar los demas valores, especificamente para 
     * asegurarnos de que no se manipulen los valores, es decir que las personas, como 
     * un programador no modifique valores como cantidad de producsot que ya existian
     */


    this.form.id_pecosa_silucia =  _row.numero;
    this.form. id_item_pecosa_silucia=  _row.idsalidadet;
    this.form.silucia_pecosa = JSON.parse(JSON.stringify(_row))
    this.showMovementModal = true;
    console.log(this.form)
  }

  closeMovementModal() {
    this.showMovementModal = false;
    this.form = {
      silucia_pecosa: [], 
      movement_type: null, 
      amount: null, 
      id_pecosa_silucia: null,
      id_item_pecosa_silucia: null,
      observations: null,
      people_dnis: [],
    };
    this.listDniPeople.set([])
  }

  // AutoComplete como dropdown
  searchMovement(event: { query: string }) {
    const q = (event.query ?? '').toLowerCase();
    this.filteredMovementOptions = q
      ? this.movementOptionsStr.filter(o => o.toLowerCase().includes(q))
      : [...this.movementOptionsStr];
  }
  
  onSubmitMovement() {
    // Captura el id del producto que estabas tocando
    this.lastSelectedKey = this.selectedProduct?.idcompradet ?? null;

    const page = this.lastProductsPage;
    const perPage = this.lastProductsRows;
    this.savingMovementLoading.set(true);
    console.log("datos enviado a backendd: ", this.form);
    
    this.service.createKardexMovement(this.form)
      .pipe(
          tap((res: any) => {
            if (res?.ok === false){
              this.showToastMessage('error', 'Ocurrio un error', 'Por favor vuelva a intentarlo');
              throw new Error(res?.message || 'Operación rechazada'); 
            } 
          }),
          switchMap((): any => {
            this.getProductsOfSiluciaBackend({ ...this.uiFilters, page, per_page: perPage });
            return of(true); // devolvemos algo para que la cadena siga y llegue al subscribe(next)
          }),
          catchError(err => { 
            this.errorLoadingProducts.set('No se pudo cargar'); 
            this.showToastMessage('error', 'Ocurrio un error', 'Por favor vuelva a intentarlo');
            // this.message = err?.error?.message || err?.message || 'Error inesperado';
            return EMPTY; // corta la cadena: no se ejecuta el next
          }),
          finalize(() => { this.savingMovementLoading.set(false) }),
          takeUntilDestroyed(this.destroyRef),
      )
      .subscribe(res => {
        this.closeMovementModal();
        // this.showToastMessage('sucess', 'Operación exitosa', 'su registro fue completo con suceso');
        this.showToastMessage('success', 'Operación exitosa', 'El registro se completó correctamente.');

      })
    
  }

  // ----- Detalles de movimiento (modal) -----
  // openMovementDetailsModal(_row?: any){
  //   // debemos hacer la peticion para obtner la tabla de detalles de movimeinto
  //   this.service.getKardexMovementBySiluciaBackend(_row.numero, _row.idcompradet).subscribe({
  //     next: res => {
  //       this.movementsKardex.set(res.movements.data) 
  //       console.log("datos de movimiento relacinados a un orden a un producto de la base de datos silucia: ", res)
  //     },
  //     error: err => { 
  //       this.errorLoadingProducts.set('No se pudo cargar');
  //     }
  //   });
  //   this.showMovementDetailsModal = true;
  // }
  // openMovementDetailsModal(row?: any){
  //     this.selectedProductForMovements = row;
  //     this.showMovementDetailsModal = true;
  //     this.fetchMovements(1, this.movementsPageSize);
  //   }
  openMovementDetailsModal(row?: any) {
    console.log("mostrar movimientos:", row);
    this.selectedPecosaForMovements = row;
    this.showMovementDetailsModal = true;
    this.expandedRowsMovements.set({});   // ← reset al abrir
    this.fetchMovements(1, this.movementsPageSize);
  }
    
  private fetchMovements(page: number, perPage: number) {
    if (!this.selectedPecosaForMovements) return;

    const orderNum = this.selectedPecosaForMovements.numero;
    // const productIdSilucia = this.selectedProductForMovements.idcompradet;
    const productIdSilucia = this.selectedPecosaForMovements.idsalidadet;

    this.movementsLoading.set(true);

    // Ajusta tu servicio para aceptar page y per_page en la URL
    this.service.getKardexMovementBySiluciaBackend(orderNum, productIdSilucia, {
      page,
      per_page: perPage,
    })
      .subscribe({
        next: (res: any) => {
          // Backend que mostraste: res.movements.{data,total,per_page}
          this.movementsKardex.set(res.movements.data);
          this.movementsTotal.set(res.movements.total);
          this.movementsPageSize = res.movements.per_page;
          // expandir todas las filas de la página actual
          // const all: Record<number, boolean> = {};
          // for (const m of res.movements.data ?? []) all[m.id] = true;
          // this.expandedRowsMovements.set(all);
          // this.expandedRowsMovements = {}; // ← limpiar al cambiar de página

          // const all = Object.fromEntries((res.movements.data ?? []).map((m: any) => [m.id, true]));
          // this.expandedRowsMovements.set(all);
          this.movementsLoading.set(false);
        },
        error: _ => {
          this.movementsLoading.set(false);
        }
      });
  }
  // getKardexMovementBySiluciaBackend(numero: number|string, idcompradet: number, page=1, perPage=50) {
  //   return this.http.get<any>(
  //     `/api/silucia-orders/${numero}/products/${idcompradet}/movements-kardex`,
  //     { params: { page, per_page: perPage } }
  //   );
  // }

  closeMovementDetailsModal(){
    this.showMovementDetailsModal = false;
    this.movementsKardex.set([]);
  }

  // Descarga del pdf
  onSubmitMovementDetails(){
    // Debemos descargar el pdf para el reporte
      console.log("detalles de pdf: ",this.selectedPecosaForMovements);
      // const id_order_silucia = this.selectedPecosaForMovements.numero;
      const id_pecosa_silucia = this.selectedPecosaForMovements.numero;
      // const id_product_silucia = this.selectedPecosaForMovements.idcompradet;
      const id_item_pecosa_silucia = this.selectedPecosaForMovements.idsalidadet;
      console.log("ide enviado: ", id_item_pecosa_silucia );
      // this.service.downloadPdf(2874,249069).subscribe(res => {
      this.service.downloadPdf(id_pecosa_silucia, id_item_pecosa_silucia).subscribe(res => {
        const blob = res.body!;
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        // si no quieres fijar nombre en front, no pongas nada especial;
        // 'a.download' vacío usa el que mande el navegador/servidor en muchos casos
        a.href = url;
        a.download = ''; 
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
      });
  }

  // downloadPdfWithProgress(
  //   id_order_silucia: number | string,
  //   id_product_silucia: number
  // ): Observable<HttpEvent<Blob>> {
  //   return this.http.get(
  //     `${this.apiUrl}/api/silucia-orders/${id_order_silucia}/products/${id_product_silucia}/movements-kardex/pdf`,
  //     {
  //       observe: 'events',          // 👈 eventos (progreso + respuesta final)
  //       reportProgress: true,       // 👈 habilita progreso
  //       responseType: 'blob',       // 👈 es un blob (PDF)
  //       withCredentials: true,
  //       headers: { Accept: 'application/pdf' }
  //     }
  //   );
  // }
  

  // ----- Placeholder (si cierras otros modales) -----
  closeMovementsModal() {
    // hook para cerrar modal principal si lo usas en otro lado
  }

  onMovementsLazyLoad(e: any) {
    // e.first = índice inicial, e.rows = tamaño de página
    const page = Math.floor((e.first ?? 0) / (e.rows ?? this.movementsPageSize)) + 1;
    const perPage = e.rows ?? this.movementsPageSize;
    this.expandedRowsMovements.set({});   // ← reset al cambiar de página
    this.fetchMovements(page, perPage);
  }


  // utilidades para calcular totales:
  
  // ...tu código existente...

  /** Utilidad segura para convertir a número */
  private toNum(v: any): number {
    const n = typeof v === 'number' ? v : parseFloat(String(v));
    return Number.isFinite(n) ? n : 0;
  }

  /** Total de ENTRADAS en la página actual del modal */
  get totalEntradas(): number {
    return (this.movementsKardex() ?? []).reduce(
      (sum, m) => sum + (m?.movement_type === 'entrada' ? this.toNum(m?.amount) : 0),
      0
    );
  }

  /** Total de SALIDAS en la página actual del modal */
  get totalSalidas(): number {
    return (this.movementsKardex() ?? []).reduce(
      (sum, m) => sum + (m?.movement_type === 'salida' ? this.toNum(m?.amount) : 0),
      0
    );
  }

  /** “Stock” según tu consigna: salidas - entradas */
  get stockSaldo(): number {
    return this.totalEntradas - this.totalSalidas ;
    // Nota: contabilidad clásica suele usar entradas - salidas; aquí respeto lo que pediste.
  }



  // ---------------- Modal para añadir persona ---------------------------

  // function handleOpenModal(){

  // }

  // onAddPerson() {
  //   // abre modal de persona o navega: hook listo
  //   console.log('Adicionar persona');
  // }
  
  closeModalAddPerson(): void {
    this.showAddUserModal = false;
  }

  OpenModalAddPerson():void{
    this.showAddUserModal = true;
  }

  handleListPeopleByDni(event: any):void{
    const nuevaPersona = event;

    // con esto mostramos en la interfaz que nombres han sido seleccionado
    this.listDniPeople.update(listaActual => {
      const yaExiste = listaActual.some(p => p.dni === nuevaPersona.dni);
      return yaExiste ? listaActual : [...listaActual, nuevaPersona];
    });

    // con esto enviamos solamente dnis
    if(this.listDniPeople().length != 0){
      const dnis = this.listDniPeople().map((object)=>{
        return object?.dni;
      })
      this.form.people_dnis = dnis;
    }

    // console.log("Persona recibida por DNI:", nuevaPersona);
    // console.log("Lista actualizada:", this.listDniPeople());
    // console.log("Datos de form:", this.form);
  }

  // -------------------expansion de filas--------------
  onRowExpandMovement(e: any) {
    console.log("value001", e)
    const id = e.data?.id;
    if (id == null) return;
    this.expandedRowsMovements.update(map => ({ ...map, [id]: true }));
  }

  onRowCollapseMovement(e: any) {
    console.log("value002", e)
    const id = e.data?.id;
    if (id == null) return;
    this.expandedRowsMovements.update(map => {
      const { [id]: _omit, ...rest } = map;
      return rest;
    });
  }

  // toast messages
  showToastMessage(severity: 'success'|'info'|'warn'|'error', summary: string, detail: string) {
      // this.messageService.add({ severity: 'error', summary: 'Error', detail: 'Message Content' });
      this.messageService.add({ severity: severity, summary: summary, detail: detail });
  }

  seeDataSelected(){
    console.log(this.form);
  }


}
