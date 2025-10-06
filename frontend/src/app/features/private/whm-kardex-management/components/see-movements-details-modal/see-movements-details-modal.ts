import { Component, DestroyRef, effect, inject, input, model, signal, untracked } from '@angular/core';
import { Button } from "primeng/button";
import { TableModule } from "primeng/table";
import { Dialog } from "primeng/dialog";
import { WhmKardexManagementService } from '../../services/whm-kardex-management.service';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { finalize } from 'rxjs';
import { parseHttpError } from '../../../../../shared/utils/parseHttpError';
import { Toast } from 'primeng/toast';
import { MessageService } from 'primeng/api';
import { KardexMovementFilters } from '../../interfaces/whm-kardex-management.interface';
import { KardexMovementDetallado } from '../../interfaces/whm-kardex-management.interface';
import { CommonModule } from '@angular/common'
import { formatServerYmdHm } from '../../../../../shared/utils/formatServerYmdHm';
@Component({
  selector: 'app-see-movements-details-modal',
  imports: [Button, TableModule, Dialog, Toast, CommonModule],
  providers: [MessageService],
  templateUrl: './see-movements-details-modal.html',
  styleUrl: './see-movements-details-modal.css'
})
export class SeeMovementsDetailsModal {
  private api = inject(WhmKardexManagementService);
  isOpen = model<boolean>(false);
  obraId = input<number | null>(null);
  ordenCompraDetalladoId = input<number>(0);
  private readonly destroyRef = inject(DestroyRef);
  private readonly messageService = inject(MessageService);
  readonly formatServerYmdHm = formatServerYmdHm
  movementDetails = signal<KardexMovementDetallado>({
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

  constructor() {
    effect(() => {
      if (!this.isOpen()) return;
      console.log("Hacemos petición");
      untracked(() => this.getKardexMovement(this.obraId(), this.ordenCompraDetalladoId(), 0, this.movementDetails().rows, this.movementDetails().filters));
    });
  }
  
  handleOpen(){
    this.isOpen.set(false);
  }

  getKardexMovement(obraId:number | null, ordenCompraDetalladoId:number, first:number, rows: number, filters: KardexMovementFilters){
      if(this.movementDetails().loading) return;
      if(!this.obraId()) return;

      const page = Math.floor(first / rows) + 1;  
      const perPage = rows;

      this.movementDetails.update(objects => ({ 
        ...objects, 
        loading: true,
        rows: rows,
        first: first,
      }));

      this.api.getKardexMovementDetails(obraId, ordenCompraDetalladoId, page, perPage, filters)
      .pipe(
          takeUntilDestroyed(this.destroyRef),
          finalize(()=>{this.movementDetails.update((data)=>{return {...data, loading: false}})})
      ).subscribe({
          next: (response) => { 
            console.log("This is the response: ", response)
            this.movementDetails.update((data)=>{
              return {...data, value: response.movements.data, totalRecords: response.movements.total}
            })
          },
          error: async (error) => {
              const p = await parseHttpError(error);
              this.messageService.add({ severity:p.severity, summary:p.title, detail:p.detail });
          }
      });
  }

  onLazyLoadMovementDetails(event:any){
    // this.getItemsPecosas(this.selectedObraId, event.first, event.rows, this.pecosasx().filters)
    this.getKardexMovement(this.obraId(), this.ordenCompraDetalladoId(), event.first, event.rows, this.movementDetails().filters);

  }

}
