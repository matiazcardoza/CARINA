import { Component, DestroyRef, inject, input, output, untracked } from '@angular/core';
import { DialogModule } from 'primeng/dialog';
import { TableModule } from 'primeng/table';
import { signal, effect } from '@angular/core';
import { WhmObrasManagementService } from '../../services/whm-obras-management.service';
import { Obra } from '../../interfaces/whm-obras-management.interface';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
// import { filters } from '../../../kardex-management/interfaces/kardex-management.interface';
import { Filters } from '../../interfaces/whm-obras-management.interface';
import { Button } from 'primeng/button';
import { InputIcon } from 'primeng/inputicon';
import { InputTextModule } from 'primeng/inputtext';
import { TooltipModule } from 'primeng/tooltip'; 
// import { ButtonDirective } from "../../../../../../../node_modules/primeng/button/index";


@Component({
    selector: 'app-show-all-obras-modal',
    imports: [DialogModule, TableModule, Button, InputIcon, InputTextModule, TooltipModule],
    templateUrl: './show-all-obras-modal.html',
    styleUrl: './show-all-obras-modal.css'
})

export class ShowAllObrasModal {
    service = inject(WhmObrasManagementService)
    isOpen = input<boolean>(false)
    isOpenChange = output<boolean>() 
    private destroyRef = inject(DestroyRef);

  tableData = signal({
      value: <Obra[]> [],   // registros (array de objetos)
      rows: 10,              // cantidad de filas que hay en cada pagina
      first: 0,             // indice (id) del primer registros que se ve por pagina (cambia cuando cambias de paginas)
      totalRecords: 0,      // total de filas que existe en la base de datos
      rowsPerPageOptions: [10,15,20],
      loading: false,
      filters: {
          anio: null,
          codmeta: null
      } as Filters
  })

  constructor(){
      effect(()=>{
          if(this.isOpen()){
              const rows = untracked(() => this.tableData().rows);
              const filters = untracked(() => this.tableData().filters);
              this.loadPage(0, rows, filters)
          }
      })
  }

  private loadPage(first: number, rows: number, filters: Filters){
    console.log("peticion para tabla");

    /**
     * Solamente lo que se debe enviar al backend es la cantidad de filas que queremos ver (perPage)
     * en una pagina y la pagina que queremos ver (page). por otro lado prime ng siempre devuelve por
     * su callback onlazyLoad los valores first (primera fila de la pagina) y rows (cantidad de filas que se muestra por pagina).
     * por ejemplo:
     *    0) first: 0 , rows: 20 ----> quiere decir que la primera fila que se esta viendo el la pagina es la fila con indice 0
     *    1) first: 20, rows: 20 ----> quiere decir que la primera fila que se esta viendo en la pagina es la fila con indice 20 
     *    2) first: 40, rows: 20
     *    3) first: 60, rows: 20
     * como el backend quiere saber la pagina y la cantidad de paginas, nosotros con esos dos datos, debemos
     * hallar la página, cantidad de filas por pagina es lo mismo que rows
     */
    const page = Math.floor(first / rows) + 1; // 1-based 
    const perPage = rows;

    this.tableData.update(objects => ({ 
      ...objects, 
      loading: true,
      rows: rows,
      first: first,
    }));

    this.service.getObrasSilucia(page, perPage, filters)
    .pipe(takeUntilDestroyed(this.destroyRef))
    .subscribe({
        next: (response) => {
          this.tableData.update((object)=>({
            ...object, 
            value: response.data ?? [],
            totalRecords: response.total ?? response.data.length,
            loading: false
          })) 
        },

        error: () => {
          this.tableData.update(objects => ({...objects, loading: false}))
        }
    })

  }

  onLazyLoad(value:any){
    console.log('valor de la tabla',value);
    this.loadPage(value.first ?? 0, value.rows, this.tableData().filters);
  }

  onAddFilters(type:string, filter: string){
    switch (type) {
      case 'anio':
        this.tableData.update((object)=>({...object, filters: {...object.filters, anio: filter}}));
        break;
      case 'codmeta':
        this.tableData.update((object)=>({...object, filters: {...object.filters, codmeta: filter}}));
        break;
      default:
        // console.log('No reconocido');
    }
  }

  search(){
    this.loadPage(0, this.tableData().rows, this.tableData().filters);
  }

  cleanFilters(){
    // solo debemos quitar los filtros y no debemos eliminar el resto
    this.tableData.update(object => ({...object, filters: {anio: null, codmeta: null}}))
    this.loadPage(0, this.tableData().rows, this.tableData().filters);
  }



  importObra(obra:any){
    if (!obra.idmeta) return; // AHORA: Leemos el valor de la señal con ()
    this.tableData.update(object => ({...object, loading: true}))
    this.service.importAndAttach(obra).subscribe({
      next: () => { 
      },
      complete: () => {
        // this.isOpenChange.emit(false)
        this.tableData.update(object => ({...object, loading: false}))
      }
    });
    
  }

  closeModal(){
    this.isOpenChange.emit(false)
  }
  receibevalue(value: number){
    console.log("value received: ", value)
  }

  verObras(){
    console.log("obras disponibles: ", this.tableData())
  }

}
