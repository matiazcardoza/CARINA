import { Component, signal } from '@angular/core';
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

// Data mock
import { clients, products } from './utils/mockup-data';

@Component({
  selector: 'app-kardex-management',
  standalone: true,
  imports: [
    // Angular
    FormsModule, SlicePipe,
    // PrimeNG
    TableModule, InputTextModule, DialogModule, InputNumberModule,
    AutoComplete, Button, Tag, IconField, InputIcon
  ],
  templateUrl: './kardex-management.html',
  styleUrl: './kardex-management.css'
})

export class KardexManagement {
  // ----- State (signals / props) -----
  customers = signal<any[]>([]);
  products = signal<any[]>([]);
  selectedCustomers!: any;
  selectedProduct: any | null = null;

  // Modales
  openModalSeeDetailsOfMovimentKardex = signal<boolean>(true);
  openModaladdMovimentKardex = signal<boolean>(true);
  showMovementModal = false;

  // Personas (opcional, listo para crecer)
  people = signal<any[]>([]);
  selectedPersonId: number | null = null;
  openAddPerson = signal(false);
  newPersonName = '';
  newPersonDocument = '';

  // Form movimiento (usaremos AutoComplete tipo dropdown con strings “Entrada/Salida”)
  movementOptionsStr: Array<'Entrada' | 'Salida'> = ['Entrada', 'Salida'];
  filteredMovementOptions: string[] = [];
  form = {
    movementType: null as 'Entrada' | 'Salida' | null,
    cantidad: null as number | null
  };

  // ----- Lifecycle -----
  ngOnInit() {
    this.customers.set(clients);
    this.products.set(products.data);
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

  onAddPerson() {
    // abre modal de persona o navega: hook listo
    console.log('Adicionar persona');
  }

  // ----- Movimiento (modal) -----
  openMovementModal(_row?: any) {
    this.showMovementModal = true;
  }

  closeMovementModal() {
    this.showMovementModal = false;
    this.form = { movementType: null, cantidad: null };
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
    this.closeMovementModal();
  }

  // ----- Placeholder (si cierras otros modales) -----
  closeMovementsModal() {
    // hook para cerrar modal principal si lo usas en otro lado
  }
}
