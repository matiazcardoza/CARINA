// show-user-details-modal.ts
import { Component, input, output, signal, computed, effect, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { forkJoin, finalize } from 'rxjs';

// PrimeNG Modules
import { DialogModule } from 'primeng/dialog';
import { TableModule } from 'primeng/table';
import { SelectModule } from 'primeng/select';
import { MultiSelectModule } from 'primeng/multiselect';
import { ButtonModule } from 'primeng/button';
import { FormsModule } from '@angular/forms'; // Necesario para ngModel

import { InputTextModule } from 'primeng/inputtext';

// Services
import { ShowUserDetailsModalService, UserObraRow, ObraLite, RoleLite, ExternalMeta } from '../../services/show-user-details-modal.service';

@Component({
  selector: 'app-show-user-details-modal',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule, // Importar FormsModule
    DialogModule,
    TableModule,
    MultiSelectModule,
    SelectModule,
    ButtonModule,
    InputTextModule,
  ],
  templateUrl: './show-user-details-modal.html',
  styleUrl: './show-user-details-modal.css'
})
export class ShowUserDetailsModal {
  isOpen = input.required<boolean>();
  user   = input<any>();
  onCloseModal = output<boolean>();

  private api = inject(ShowUserDetailsModalService);

  loading = signal(false);
  obrasUsuario = signal<UserObraRow[]>([]);
  catalogObras = signal<ObraLite[]>([]);
  catalogRoles = signal<RoleLite[]>([]);
  obraToAdd = signal<number | null>(null);
  // rolesToAdd = signal<string[]>([]);
  rolesEdit = signal<Record<number, string[]>>({});

  search = {
    idmeta: '',
    codmeta: '',
    anio: ''
  };

  searchResults = signal<ExternalMeta[]>([]);
  // selectedExternal?: ExternalMeta;
  selectedExternal = signal<ExternalMeta | undefined>(undefined);
  rolesToAdd = signal<string[]>([]);

  availableObras = computed(() => {
    const assignedObraIds = new Set(this.obrasUsuario().map(x => x.obra.id));
    return this.catalogObras().filter(o => !assignedObraIds.has(o.id));
  });

  // constructor() {
  //   effect(() => {
  //     console.log("function started - loadAll")
  //     if (this.isOpen() && this.user()) {
  //       this.loadAll();
  //     }
  //   });
  // }

  // ngOnInit() {
  //   effect(() => {
  //     if (this.isOpen() && this.user()) this.loadUserData();
  //   });
  // }

  constructor(){
    effect(() => {
      if (this.isOpen() && this.user()) this.loadUserData();
    });
  }
  // loadUserData() {
  //   const u = this.user();
  //   if (!u) return;
  //   this.loading.set(true);
  //   Promise.all([
  //     this.api.getUserObras(u.id).toPromise(),
  //     this.api.getCatalogRoles().toPromise(),
  //   ]).then(([obras, roles]) => {
  //     this.obrasUsuario.set(obras ?? []);
  //     this.catalogRoles.set(roles ?? []);
  //     const seed: Record<number,string[]> = {};
  //     (obras ?? []).forEach(r => seed[r.obra.id] = [...r.roles]);
  //     this.rolesEdit.set(seed);
  //   }).finally(() => this.loading.set(false));
  // }
  loadUserData() {
    const u = this.user();
    if (!u) return;
    this.loading.set(true);

    forkJoin({
      obras: this.api.getUserObras(u.id),
      roles: this.api.getCatalogRoles(),
    })
    .pipe(
      // ✅ USA EL OPERADOR 'finalize' AQUÍ
      // Esto se ejecutará siempre al final, ya sea con éxito o con error.
      finalize(() => this.loading.set(false))
    )
    .subscribe({
      next: ({ obras, roles }) => {
        this.obrasUsuario.set(obras ?? []);
        this.catalogRoles.set(roles ?? []);
        const seed: Record<number, string[]> = {};
        (obras ?? []).forEach(r => seed[r.obra.id] = [...r.roles]);
        this.rolesEdit.set(seed);
      },
      error: (err) => console.error('Error al cargar datos del usuario', err),
      // finally: () => this.loading.set(false)
    });
  }

  doSearch() {
    this.loading.set(true);
    this.api.searchExternalObras({
      idmeta: this.search.idmeta?.trim(),
      codmeta: this.search.codmeta?.trim(),
      anio: this.search.anio?.trim(),
      per_page: 10,
      page: 1
    }).subscribe({
      next: (resp) => {
        const rows = resp?.data ?? []; // tu MetaClient devuelve {current_page,data,...}
        this.searchResults.set(rows.map((m: any) => ({
          idmeta: m.idmeta, anio: m.anio, codmeta: m.codmeta,
          nombre_corto: m.nombre_corto, desmeta: m.desmeta
        })));
      },
      complete: () => this.loading.set(false)
    });
  }

  pickExternal(m: ExternalMeta) {
    // this.selectedExternal = m;
    this.selectedExternal.set(m); // AHORA: Usamos .set() para actualizar la señal
    this.rolesToAdd.set([]); // limpia selección de roles iniciales
  }

  importAttach() {
    const u = this.user();
    // if (!u || !this.selectedExternal) return;
    if (!u || !this.selectedExternal()) return; // AHORA: Leemos el valor de la señal con ()

    this.loading.set(true);
    // this.api.importAndAttach(u.id, this.selectedExternal, this.rolesToAdd()).subscribe({
    this.api.importAndAttach(u.id, this.selectedExternal()!, this.rolesToAdd()).subscribe({

      next: () => { 
        // recarga tabla local del usuario y limpia selección
        this.loadUserData();
        // this.selectedExternal = undefined;
        this.selectedExternal.set(undefined); // AHORA: Limpiamos la señal
        this.rolesToAdd.set([]);
      },
      complete: () => this.loading.set(false)
    });
  }

  updateRolesForRow(obraId: number, newRoles: string[]) {
    this.rolesEdit.update(curr => ({ ...curr, [obraId]: newRoles }));
  }

  syncRoles(obraId: number) {
    const u = this.user();
    if (!u) return;
    const roles = this.rolesEdit()[obraId] ?? [];
    this.loading.set(true);
    this.api.syncRoles(u.id, obraId, roles).subscribe({
      next: () => this.loadUserData(),
      complete: () => this.loading.set(false)
    });
  }

  loadAll(): void {
    console.log("function started - loadAll")
    const currentUser = this.user();
    if (!currentUser) return;

    this.loading.set(true);

    forkJoin({
      obras: this.api.getUserObras(currentUser.id),
      catObras: this.api.getCatalogObras(),
      catRoles: this.api.getCatalogRoles()
    }).subscribe({
      next: ({ obras, catObras, catRoles }) => {
        console.log("01 - obras de usuario: ", obras);
        console.log("02 - catalogo de obras: ", catObras);
        console.log("03 - catalogo de roles: ", catRoles);
        this.obrasUsuario.set(obras ?? []);
        this.catalogObras.set(catObras ?? []);
        this.catalogRoles.set(catRoles ?? []);
        
        const seed: Record<number, string[]> = {};
        (obras ?? []).forEach(r => seed[r.obra.id] = [...r.roles]);
        this.rolesEdit.set(seed);

        console.log("resultado de seed: ", seed);
      },
      error: (err) => console.error('Error al cargar los datos', err),
      complete: () => this.loading.set(false)
    });
  }



  // updateRolesForRow(obraId: number, newRoles: string[]): void {
  //   console.log("function started - updateRolesForRow")
  //   this.rolesEdit.update(currentRoles => ({
  //     ...currentRoles,
  //     [obraId]: newRoles
  //   }));
  // }

  addObra(): void {
    console.log("function started - addObra");
    const currentUser = this.user();
    const obraId = this.obraToAdd();
    if (!currentUser || !obraId) return;

    this.loading.set(true);
    this.api.addObra(currentUser.id, obraId, this.rolesToAdd()).subscribe({
      next: () => {
        this.loadAll();
        this.obraToAdd.set(null);
        this.rolesToAdd.set([]);
      },
      complete: () => this.loading.set(false)
    });
  }

  // removeObra(obraId: number): void {
  //   console.log("function started - removeObra");
  //   const currentUser = this.user();
  //   if (!currentUser) return;

  //   this.loading.set(true);
  //   this.api.removeObra(currentUser.id, obraId).subscribe({
  //     next: () => this.loadAll(),
  //     complete: () => this.loading.set(false)
  //   });
  // }
  removeObra(obraId: number) {
    const u = this.user();
    if (!u) return;
    this.loading.set(true);
    this.api.removeObra(u.id, obraId).subscribe({
      next: () => this.loadUserData(),
      complete: () => this.loading.set(false)
    });
  }

  // syncRoles(obraId: number): void {
  //   console.log("function started - syncRoles");
  //   const currentUser = this.user();
  //   if (!currentUser) return;
  //   const roles = this.rolesEdit()[obraId] ?? [];

  //   this.loading.set(true);
  //   this.api.syncRoles(currentUser.id, obraId, roles).subscribe({
  //     next: () => this.loadAll(),
  //     complete: () => this.loading.set(false)
  //   });
  // }

  // close(val = false): void {
  //   console.log("function started - close");
  //   this.onCloseModal.emit(val);
  // }
  close(val=false){ this.onCloseModal.emit(val); }
}