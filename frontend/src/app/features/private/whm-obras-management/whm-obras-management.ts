// src/app/features/whm/obras/whm-obras-management.ts
import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { TableModule } from 'primeng/table';
import { Button } from 'primeng/button';
import { InputTextModule } from 'primeng/inputtext';
import { Toast } from 'primeng/toast';
import { MessageService } from 'primeng/api';
import { WhmObrasManagementService, Obra, Paginated } from './services/whm-obras-management.service';
import { ButtonModule } from 'primeng/button';
import { ShowAllObrasModal } from './components/show-all-obras-modal/show-all-obras-modal';


@Component({
  selector: 'app-whm-obras-management',
  standalone: true,
  imports: [TableModule, Button, InputTextModule, Toast, FormsModule, ButtonModule, ShowAllObrasModal],
  providers: [MessageService],
  templateUrl: './whm-obras-management.html',
  styleUrls: ['./whm-obras-management.css'],
})
export class WhmObrasManagement {
  private api = inject(WhmObrasManagementService);
  private msg = inject(MessageService);

  obras   = signal<Obra[]>([]);
  total   = signal(0);
  loading = signal(false);
  showAllObrasModal = signal<boolean>(false)

  // paginación (server-side)
  rows  = signal(10);  // tamaño de página
  first = signal(0);   // índice base 0

  // búsqueda
  filtro = signal('');

  ngOnInit() {
    this.loadPage();
  }

  loadPage() {
    this.loading.set(true);
    const page    = this.first() / this.rows() + 1;
    const perPage = this.rows();
    const search  = this.filtro().trim();

    this.api.getObras(page, perPage, search).subscribe({
      next: (res: any) => {      // <-- tipa 'res'
        this.obras.set(res.data);            // OK
        this.total.set(res.total);           // OK
      },
      error: () => this.msg.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar obras.' }),
      complete: () => this.loading.set(false),
    });
  }

  onLazyLoad(ev: any) {
    this.first.set(ev.first ?? 0);
    this.rows.set(ev.rows ?? 10);
    this.loadPage();
  }

  onSearch() {
    this.first.set(0);
    this.loadPage();
  }

  onImport(obra: Obra) {
    this.msg.add({ severity: 'info', summary: 'Importando...', detail: obra.nombre || obra.codmeta, life: 1500 });
    this.api.importUsers(obra.id).subscribe({
      next: (res: any) => {
        this.msg.add({
          severity: 'success',
          summary: 'Importación completada',
          detail: `Usuarios creados: ${res.import_summary?.created_users ?? 0}, personas act./creadas: ${res.import_summary?.updated_personas ?? 0}, asignados: ${res.import_summary?.attached_to_obra ?? 0}`,
          life: 5000,
        });
      },
      error: (err) => {
        this.msg.add({ severity: 'error', summary: 'Error al importar', detail: err?.error?.message || 'Revise el log.' });
      },
    });
  }

  getObras(){
    this.showAllObrasModal.set(true)
    console.log("importar obra")
  }
  cerrarModal(){
    this.showAllObrasModal.set(false)
  }
}
