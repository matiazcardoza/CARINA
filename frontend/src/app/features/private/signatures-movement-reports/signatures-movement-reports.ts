import { CommonModule, SlicePipe } from '@angular/common';
import { Component, ViewChild, signal, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { TableModule, Table, TableLazyLoadEvent } from 'primeng/table';
import { InputTextModule } from 'primeng/inputtext';
import { DialogModule } from 'primeng/dialog';
import { InputNumberModule } from 'primeng/inputnumber';
import { AutoComplete } from 'primeng/autocomplete';
import { Button } from 'primeng/button';
import { Tag } from 'primeng/tag';
import { IconField } from 'primeng/iconfield';
import { InputIcon } from 'primeng/inputicon';
// import { AddNewUserModal } from '../kardex-management/components/add-new-user-modal/add-new-user-modal';
import { ListboxModule } from 'primeng/listbox';
import { KardexManagementService } from '../kardex-management/services/kardex-management.service';
import { DigitalSignatureService } from '../../../shared/draft/digital-signature/services/digital-signature.service';
import { SignatureParams } from '../../../shared/draft/digital-signature/interface/signature-params.interface';
type userStep = {
  height: number;
  id: number;
  order: number;
  page: number;
  pos_x: number;
  pos_y: number;
  role: string;
  status: string;
  width: number;
}

type Report = {
  report_id: number;
  type: string | null;
  period: { from: string | null; to: string | null } | null;
  status: 'in_progress' | 'signed' | 'rejected' | string;
  flow_id: number;
  current_step: number;
  current_role: string;          // 'almacenero' | 'residente' | etc.
  user_step: userStep | null;
  can_sign: boolean;
  download_url: string;
  sign_callback_url: string | null;
};

type ProductRow = {
  product_id: number;
  name: string | null;
  id_order_silucia: string;
  id_product_silucia: number;
  fecha?: string | null;
  detalles_orden?: string | null;
  desmeta?: string | null;
  reports: Report[];
};

type LaravelPage<T> = {
  data: T[];
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
};

@Component({
  selector: 'app-signatures-movement-reports',
  standalone: true,
  imports: [
    FormsModule,
    // SlicePipe,
    TableModule,
    InputTextModule,
    DialogModule,
    InputNumberModule,
    // AutoComplete,
    Button,
    Tag,
    IconField,
    InputIcon,
    // AddNewUserModal,
    ListboxModule,
    CommonModule
  ],
  templateUrl: './signatures-movement-reports.html',
  styleUrl: './signatures-movement-reports.css'
})
export class SignaturesMovementReports {
  private dataSrv = inject(KardexManagementService);
  private signature = inject(DigitalSignatureService);

  @ViewChild('dt1') dt1!: Table;

  // Estado
  products = signal<ProductRow[]>([]);
  productsTotal = signal<number>(0);
  loadingProducts = signal<boolean>(false);

  // UI
  pageSize = 10;

  // Para expansión en el 1er nivel (productos)
  expandedRowsProducts = signal<Record<string, boolean>>({});

  // Filtros del caption (si tu backend los soporta)
  uiFilters: any = {
    numero: '',
    anio: '',
    siaf: '',
    ruc: '',
    rsocial: '',
    item: '',
    desmeta: '',
    email: ''
  };

  private lastLazyEvent: TableLazyLoadEvent | null = null;

  // -------- PrimeNG lazy --------
  onProductsLazyLoad(event: TableLazyLoadEvent) {
    this.lastLazyEvent = event;
    this.fetchProducts(event);
  }

  onSearchClick() {
    const base: TableLazyLoadEvent = this.lastLazyLoadEventOrDefault();
    const event: TableLazyLoadEvent = { ...base, first: 0, rows: this.pageSize };
    this.onProductsLazyLoad(event);
  }

  onClearClick() {
    this.uiFilters = {
      numero: '',
      anio: '',
      siaf: '',
      ruc: '',
      rsocial: '',
      item: '',
      desmeta: '',
      email: ''
    };
    this.onSearchClick();
  }

  private lastLazyLoadEventOrDefault(): TableLazyLoadEvent {
    return this.lastLazyEvent ?? { first: 0, rows: this.pageSize };
  }

  // -------- Fetch (productos + reports anidados) --------
  private fetchProducts(event: TableLazyLoadEvent) {
    this.loadingProducts.set(true);

    const first = event.first ?? 0;
    const rows  = event.rows  ?? this.pageSize;
    const page  = Math.floor(first / rows) + 1;

    const sort_field = (event.sortField as string) || 'id_order_silucia';
    const sort_order = event.sortOrder === -1 ? 'desc' : 'asc';

    const payload = {
      // Filtros (si tu API los espera):
      numero:  this.uiFilters.numero?.trim() || undefined,
      anio:    this.uiFilters.anio ? Number(this.uiFilters.anio) : undefined,
      siaf:    this.uiFilters.siaf?.trim() || undefined,
      ruc:     this.uiFilters.ruc?.trim() || undefined,
      rsocial: this.uiFilters.rsocial?.trim() || undefined,
      item:    this.uiFilters.item?.trim() || undefined,
      desmeta: this.uiFilters.desmeta?.trim() || undefined,
      email:   this.uiFilters.email?.trim() || undefined,

      // Paginación + sort:
      page,
      per_page: rows,
      sort_field,
      sort_order
    } as const;

    // this.dataSrv.getPdfReporter(payload).subscribe({
    this.dataSrv.getProductsFromOwnDatabase(payload).subscribe({
      next: (res: LaravelPage<ProductRow>) => {
        this.products.set(res.data ?? []);
        this.productsTotal.set(res.total ?? 0);
        this.loadingProducts.set(false);
      },
      error: (err) => {
        console.error('Error loading products', err);
        this.products.set([]);
        this.productsTotal.set(0);
        this.loadingProducts.set(false);
      }
    });
  }

  // -------- Expansión filas (productos) --------
  onRowExpandProduct(e: any) {
    const id = String(e.data.product_id);
    this.expandedRowsProducts.update(k => ({ ...k, [id]: true }));
  }

  onRowCollapseProduct(e: any) {
    const id = String(e.data.product_id);
    const { [id]: _, ...rest } = this.expandedRowsProducts();
    this.expandedRowsProducts.set(rest);
  }

  // -------- Acciones sobre reports --------
  onDownloadReport(r: Report) {
    if (!r?.download_url) return;
    window.open(r.download_url, '_blank');
  }

  onSignReport(r: Report) {
    if (!r?.can_sign) return;
    // Si tu backend expone un callback, puedes abrirlo:
    if (r.sign_callback_url) {
      window.open(r.sign_callback_url, '_blank');
      return;
    }
    // O llamar a un endpoint propio (ejemplo):
    // this.dataSrv.signReport(r.report_id).subscribe(...);
  }

  // -------- UI helpers --------
  getStatusSeverity(status: string): 'success'|'warning'|'danger'|'info'|'secondary' {
    switch (status) {
      case 'signed': return 'success';
      case 'in_progress': return 'warning';
      case 'rejected': return 'danger';
      default: return 'info';
    }
  }

  // dentro de la clase
  working = false;
  message = '';
  
  sign(r: Report) {
    console.log("datos de peticion r: ", r);
    // r.download_url = 'http://127.0.0.1:8000/api/payments/PDF_NUMERO_1.pdf';
      // 1) Guards básicos
    if (!r) return;
    if (!r.download_url) {
      this.message = 'No hay URL de descarga del PDF.';
      return;
    }
    if (!r.sign_callback_url) {
      // Cuando no te toca, el backend puede devolver null. Igual mostramos por qué:
      this.message = 'No hay callback para este usuario o paso. Verifica si te toca firmar.';
      return;
    }
    if (!r.can_sign) {
      this.message = 'Aún no es tu turno de firma (botón deshabilitado).';
      return;
    }

    // const callback = `${this.environment.API_URL}api/signatures/callback` + `?flow_id=${flowId}&step_id=${stepId}&token=${callbackToken}`;
    this.working = true;
    this.message = '';

    // 2) Armar parámetros para el proveedor
    const params: SignatureParams = {
      location_url_pdf: r.download_url,        // <-- tu backend expone el PDF vigente
      // location_url_pdf: `http://127.0.0.1:8000/api/payments/PDF_NUMERO_1.pdf`,        // <-- tu backend expone el PDF vigente
      post_location_upload: r.sign_callback_url, // <-- incluye flow_id, step_id, token
      // post_location_upload: `http://127.0.0.1:8000/api/signatures/callback?valor=12312312312`,

      // Los siguientes valores puedes dejarlos fijos o traerlos del backend si los guardas por paso:
      rol: r.current_role ?? 'firmante',
      // tipo: r.type ?? 'kardex',

      // rol: 'Jefe de RRHH',
      tipo: 'recursos',

      visible_position: false,
      bacht_operation: false,
      npaginas: r?.user_step?.page,
      // posx: 120,
      posx: r?.user_step?.pos_x,
      // posx: 445,
      posy: r?.user_step?.pos_y,
      // posy: 745,
      // posy: 725,
      token: ''
    };

    // 3) Abrir el firmador
    this.working = true;
    this.message = '';
    const PROVEEDOR_URL = 'https://sistemas.regionpuno.gob.pe/firma-api/';

    this.signature.openSignatureWindow$(params, PROVEEDOR_URL).subscribe({
        next: () => {
          this.message = '✅ Firma completada y recibida por el backend.';
          // 4) Refrescar la página/tabla para que cambie el estado (current_step, can_sign, etc.)
          const base = this.lastLazyLoadEventOrDefault();
          this.onProductsLazyLoad({ ...base });
        },
        error: (err) => {
          this.message = `❌ ${err?.message || 'Error de firma'}`;
        },
        complete: () => {
          this.working = false;
        }
    });
  }

  roleLabels: Record<string, string> = {
    almacen_supervisor: 'Supervisor',
    almacen_residente: 'Residente',
    almacen_administrador: 'Administrador',
    almacen_almacenero: 'Almacenero',
    // agrega más según tu sistema
  };
// in_progress|completed|cancelled
  statusLabels: Record<string, string> = {
    in_progress: 'En progreso',
    completed: 'Completado',
    cancelled: 'Cancelado',
  };

}
