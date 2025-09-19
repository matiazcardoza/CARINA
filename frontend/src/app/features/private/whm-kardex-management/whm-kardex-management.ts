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
import { WhmKardexManagementService, ObraLite, PecosaLite, MovementsPage } from './services/whm-kardex-management.service';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';

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
  obras = signal<ObraLite[]>([]);
  selectedObraId: number | null = null;
  pecosas = signal<PecosaLite[]>([]);
  loadingPecosas = signal<boolean>(false);
  productsTotal = signal<number>(0);
  pageSize = 20;
  selectedProduct: any | null = null;
  uiFilters: { numero?: string; anio?: number | undefined; item?: string; desmeta?: string } = {
    numero: '',
    anio: undefined,
    item: '',
    desmeta: '',
  };
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
  ngOnInit(): void {
    this.api.getObras()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (list) => {
          this.obras.set(list ?? []);
          if (this.obras().length) {
            const first = this.obras()[0];
            if (first) this.onObraChange(first.id);
          }
        }
      });
  }

  onObraChange(obraId: number) {
    this.selectedObraId = obraId;
    this.loadFirstPage();
  }


  onSearchClick() {
    const raw = this.uiFilters.anio as any;
    const anio = raw === '' || raw == null ? undefined : Number(raw);
    this.getItemPecosasOfBackend({ ...this.uiFilters, anio, page: 1, per_page: this.pageSize });
  }

  onClearClick() {
    this.uiFilters = { numero: '', anio: undefined, item: '', desmeta: '' };
    this.getItemPecosasOfBackend({ page: 1, per_page: this.pageSize });
  }

  private loadFirstPage() {
    this.getItemPecosasOfBackend({ ...this.uiFilters, page: 1, per_page: this.pageSize });
  }

  getItemPecosasOfBackend(filters: { page?: number; per_page?: number; numero?: string; anio?: number }) {
    console.log("obtener pecosas");
    
    if (!this.selectedObraId) return;
    this.loadingPecosas.set(true);
    this.api.getItemPecosas(this.selectedObraId, filters)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (res) => {
          this.pecosas.set(res.data ?? []);
          this.productsTotal.set(res.total ?? res.data?.length ?? 0);
          this.pageSize = res.per_page ?? this.pageSize;
          this.loadingPecosas.set(false);

          if (this.lastSelectedKey != null) {
            const match = (res.data ?? []).find((r: any) => String(r.idsalidadet_silucia) === String(this.lastSelectedKey));
            this.selectedProduct = match || null;
          }
        },
        error: _ => { this.loadingPecosas.set(false); }
      });
  }

  onPageChange(e: any) {
    const first = e.first ?? 0;        // El índice del primer registro de la página
    const rows = e.rows ?? this.pageSize; // El número de filas por página
    const page = Math.floor(first / rows) + 1;

    this.lastProductsPage = page;
    this.lastProductsRows = rows;

    this.getItemPecosasOfBackend({ ...this.uiFilters, page, per_page: rows });
  }

  openMovementModal(row: PecosaLite) {
    this.form.id_pecosa_silucia = row.numero;
    this.form.id_item_pecosa_silucia = Number(row.idsalidadet_silucia);
    this.form.silucia_pecosa = JSON.parse(JSON.stringify(row));
    this.showMovementModal = true;
  }

  closeMovementModal() {
    this.showMovementModal = false;
    this.form = {
      silucia_pecosa: null,
      movement_type: null,
      amount: null,
      id_pecosa_silucia: null,
      id_item_pecosa_silucia: null,
      observations: null,
      people_dnis: [],
    };
    this.listDniPeople.set([]);
  }

  onSubmitMovement() {
    console.log("movimiento enviado 001.")
    this.lastSelectedKey = this.selectedProduct?.idsalidadet_silucia ?? null;
    const page = this.lastProductsPage;
    const perPage = this.lastProductsRows;

    this.savingMovementLoading.set(true);
    console.log("formulario enviado",this.form);
    this.api.createKardexMovement(this.form, this.form?.silucia_pecosa?.id, this.selectedObraId)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (res: any) => {
          if (res?.ok === false) {
            this.toast('error', 'Ocurrió un error', 'Por favor vuelva a intentarlo');
            return;
          }
          this.closeMovementModal();
          this.toast('success', 'Operación exitosa', 'El registro se completó correctamente.');
          this.getItemPecosasOfBackend({ ...this.uiFilters, page, per_page: perPage });
        },
        error: _ => this.toast('error', 'Error', 'Por favor vuelva a intentarlo'),
        complete: () => this.savingMovementLoading.set(false)
      });
  }

  openMovementDetailsModal(row: PecosaLite) {
    console.log("selectad data: ");
    console.log(row);
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
    this.api.downloadPdf(this.selectedObraId, idItemPecosa).subscribe(res => {
      const blob = res.body!;
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = '';
      document.body.appendChild(a); a.click(); a.remove();
      setTimeout(() => URL.revokeObjectURL(url), 1000);
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
  onRowExpandPecosa(data:any){
    console.log("expandir fila: ", data)
  }
  onRowCollapsePecosa(data: any){
    console.log("colapsar fila: ", data)
  }
}
