import { Component, DestroyRef, inject, Signal, signal, effect  } from '@angular/core';
import { SlicePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';

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
@Component({
  selector: 'app-kardex-management',
  standalone: true,
  imports: [
    // Angular
    FormsModule, SlicePipe,
    // PrimeNG
    TableModule, InputTextModule, DialogModule, InputNumberModule,
    AutoComplete, Button, Tag, IconField, InputIcon,AddNewUserModal, ListboxModule
  ],
  templateUrl: './kardex-management.html',
  styleUrl: './kardex-management.css'
})

export class KardexManagement {
  // ----- State (signals / props) -----
  customers = signal<any[]>([]);
  products = signal<any[]>([]);
  loadingProducts = signal<boolean>(false);
  errorLoadingProducts = signal<string>('');
  selectedCustomers!: any;
  selectedProduct: any | null = null;
  filterProducts =  signal<string>('');
  listDniPeople = signal<any[]>([]);
  // expandedRowsMovements: { [id: number]: boolean } = {};
  expandedRowsMovements = signal<Record<number, boolean>>({});

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
    movement_type: null as 'entrada' | 'salida' | null,
    amount: null as number | null,
    id_order_silucia: null as string | null,
    id_product_silucia: null as number |null,
    observations: null as string |null,
    people_dnis: [] as string[]
  };

  movementsKardex    = signal<any[]>([]);
  movementsLoading   = signal<boolean>(false);
  movementsTotal     = signal<number>(0);
  movementsPageSize  = 50; // lo ajustaremos con lo que devuelva el backend
  selectedProductForMovements: any | null = null;

  private readonly destroyRef = inject(DestroyRef); 
  constructor(private service:KardexManagementService){
    effect(()=>{
      console.log("valor inicial del filterProducts: ", this.filterProducts());
    })
  }

  // ----- Lifecycle -----

  ngOnInit() {
    this.getProductsOfSiluciaBackend({});
    // this.customers.set(clients);
    // this.products.set(products.data)
  }

  getProductsOfSiluciaBackend(filters:{ numero?: string; anio?: number; estado?: string }){
    console.log("Numero enviado", filters.numero);
    this.loadingProducts.set(true);
    this.service.getSiluciaProducts(filters).pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: res => { this.products.set(res.data); this.loadingProducts.set(false)},
      error: err => { this.errorLoadingProducts.set('No se pudo cargar'); this.loadingProducts.set(false) }
    });
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
    this.form.id_order_silucia =  _row.numero;
    this.form.id_product_silucia=  _row.idcompradet;
    this.showMovementModal = true;
    console.log(this.form)
  }

  closeMovementModal() {
    this.showMovementModal = false;
    this.form = { 
      movement_type: null, 
      amount: null, 
      id_order_silucia: null ,
      id_product_silucia: null ,
      observations: null,
      people_dnis: []
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
    // Aquí puedes mapear si necesitas valor interno en minúsculas:
    // const value = this.form.movementType === 'Entrada' ? 'entrada' : 'salida';
    console.log('Movimiento:', this.form);
    console.log('Datos de formulario seleccionados: ', this.filteredMovementOptions);
    
    this.service.createKardexMovement(this.form).pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: res => { 
        this.getProductsOfSiluciaBackend({});
      },
      error: err => { 
        this.errorLoadingProducts.set('No se pudo cargar');
      }
    });
    this.closeMovementModal();
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
    this.selectedProductForMovements = row;
    this.showMovementDetailsModal = true;
    this.expandedRowsMovements.set({});   // ← reset al abrir
    this.fetchMovements(1, this.movementsPageSize);
  }
    
    private fetchMovements(page: number, perPage: number) {
    if (!this.selectedProductForMovements) return;

    const orderNum = this.selectedProductForMovements.numero;
    const productIdSilucia = this.selectedProductForMovements.idcompradet;

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

  onSubmitMovementDetails(){
    // Debemos descargar el pdf para el reporte
      console.log("detalles de pdf: ",this.selectedProductForMovements);
      const id_order_silucia = this.selectedProductForMovements.numero;
      const id_product_silucia = this.selectedProductForMovements.idcompradet;
      // this.service.downloadPdf(2874,249069).subscribe(res => {
      this.service.downloadPdf(id_order_silucia,id_product_silucia).subscribe(res => {
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
}
