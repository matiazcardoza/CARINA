// import { Component } from '@angular/core';
// import { WhmKardexManagementService } from './services/whm-kardex-management.service';
// @Component({
//   selector: 'app-whm-kardex-management',
//   imports: [],
//   templateUrl: './whm-kardex-management.html',
//   styleUrl: './whm-kardex-management.css'
// })
// export class WhmKardexManagement {
//   constructor(private service: WhmKardexManagementService){

//   }

//   ngOnInit(){
//     this.service.get().subscribe({
//       next: ()=>{ console.log("recurso cargado")}
//     })
//   }
// }
// --------------------------------------------------------------------
// src/app/features/whm/whm-kardex-management.component.ts
import { afterNextRender, Component, OnInit, Signal, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TableModule } from 'primeng/table';
import { ButtonModule } from 'primeng/button';

import { WhmKardexManagementService, Obra, OC, OCx, Pecosa } from './services/whm-kardex-management.service';

@Component({
  selector: 'app-whm-kardex-management',
  standalone: true,
  imports: [CommonModule, FormsModule, TableModule, ButtonModule],
  templateUrl: './whm-kardex-management.html',
  styleUrl: './whm-kardex-management.css'
})



export class WhmKardexManagement implements OnInit {
  
// obras
  // obras: Obra[] = [];
  
  // obras: signal<Obra[]>([]);
  obras = signal<Obra[]>([])
  // selectedObraId = signal<Obra[]>([])
  selectedObraId: number | null = null;

  // órdenes de compra
  // ordenes: OC[] = [];
  // ordenes = signal<OC[]>([]);
  ordenes = signal<OCx[]>([]);
  expandedRowsOrders = signal<Record<string, boolean>>({}); // ← controla expansión como en tu ejemplo funcional

  loadingOrdenes = false;

  // expansión / pecosas
  expandedRows: { [key: number]: boolean } = {};
  // loadingChildren: { [key: number]: boolean } = {};
  loadingChildren = signal<Record<number, boolean>>({});
  // pecosasByOrden: { [ordenId: number]: Pecosa[] } = {};
  // pecosasByOrden = signal<{ [ordenId: number]: Pecosa[] }>({});
  // pecosasByOrden = signal<{ [ordenId: number]: Pecosa[] }>({});
  pecosasByOrden = signal<Record<number, Pecosa[]>>({});

  // (opcional) filtros UI simples
  uiFilters = { search: '' };

  constructor(private api: WhmKardexManagementService) {}

  ngOnInit(): void {
    this.api.getObras().subscribe(list => {
      // this.obras = list ?? [];
      console.log("lista de obras: ", list);
      this.obras.set(list ?? []); 
      if (this.obras().length) {
        const first = this.obras()[0];
        if (first) this.onObraChange(first.id);
      }
    });
  }

  onObraChange(obraId: number) {
    console.log("change de value selected: ", obraId);
    this.selectedObraId = obraId;
    this.expandedRows = {};
    // this.pecosasByOrden = {};
    this.pecosasByOrden.set({});
    this.cargarOrdenes();
  }
  onRowExpandOC(e: any) {
    const oc = e.data as OCx;
    const id = String(oc.id);

    // marca como expandido (como en tu componente de ejemplo)
    this.expandedRowsOrders.update(k => ({ ...k, [id]: true }));

    // si ya cargaste, no vuelvas a pedir
    if (oc.pecosas) return;

    // setea loading en la fila
    this.ordenes.update(list => list.map(o => o.id === oc.id ? ({ ...o, childLoading: true }) : o));

    // trae pecosas y colócalas dentro de la fila
    this.api.getPecosasDeOrden(this.selectedObraId!, oc.id).subscribe({
      next: (rows) => {
        this.ordenes.update(list => list.map(o => (
          o.id === oc.id ? ({ ...o, pecosas: rows ?? [] }) : o
        )));
      },
      complete: () => {
        this.ordenes.update(list => list.map(o => (
          o.id === oc.id ? ({ ...o, childLoading: false }) : o
        )));
      }
    });
  }
  
  onRowCollapseOC(e: any) {
    const id = String((e.data as OCx).id);
    this.expandedRowsOrders.update(k => {
      const { [id]: _, ...rest } = k;
      return rest;
    });
  }
  cargarOrdenes() {
    if (!this.selectedObraId) return;
    this.loadingOrdenes = true;
    this.api.getOrdenesCompra(this.selectedObraId, this.uiFilters.search).subscribe({
      next: rows => this.ordenes.set((rows ?? []) as OCx[]),
      complete: () => { this.loadingOrdenes = false; }
    });
  }

  // se llama al expandir una fila de OC
  onRowExpand(oc: OC) {
    if (!this.selectedObraId) return;
    // if (this.pecosasByOrden[oc.id]) return;        // cache simple
    if (this.pecosasByOrden()[oc.id] !== undefined) return;
    this.loadingChildren.update(m => ({ ...m, [oc.id]: true }));
    this.api.getPecosasDeOrden(this.selectedObraId, oc.id).subscribe({
    next: rows => {
      this.pecosasByOrden.update(map => ({
        ...map,
        [oc.id]: rows ? [...rows] : []   // copia defensiva
      }));
    },
    complete: () => {
      this.loadingChildren.update(m => ({ ...m, [oc.id]: false }));
    }
  });
  }

  // helpers UI
  isExpanded(ocId: number) { return !!this.expandedRows[ocId]; }

  toggleRow(oc: OC) {
    // console.log("desplegable abierto con codigo: ", oc);
    // console.log("contenido de expandedRows: ", this.expandedRows);
    this.expandedRows[oc.id] = !this.expandedRows[oc.id];
    if (this.expandedRows[oc.id]) this.onRowExpand(oc);
  }

  // acciones pedidas (botones de cada pecosa)
  openMovementDetailsModal(p: Pecosa) { console.log('ver detalles', p); }
  openMovementModal(p: Pecosa) { console.log('agregar movimiento', p); }
  
}

