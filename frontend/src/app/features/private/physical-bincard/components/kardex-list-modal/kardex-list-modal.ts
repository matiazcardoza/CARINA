import { Component, effect, input, output, signal } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { MovementKardex } from '../../interface/movement-kardex.interface';
import { products } from '../../interface/products.interface';
import { MyPersonalGetService } from '../../services/my-personal-get.service';
import { takeUntilDestroyed, toObservable } from '@angular/core/rxjs-interop';
// import { catchError, finalize, switchMap } from 'rxjs';
import { switchMap, catchError, finalize, of, EMPTY } from 'rxjs';

@Component({
  selector: 'app-kardex-list-modal',
  imports: [MatIconModule],
  templateUrl: './kardex-list-modal.html',
  styleUrl: './kardex-list-modal.css'
})

export class KardexListModal {
  kardexList = signal<MovementKardex[]>([])
  productItem = input<products>()
  closeModal = output<any>()
    
  loading = signal<boolean>(false);
  error = signal<string | null>(null);
  // isOpen = input<boolean>(false)
  // sentOpenValue = output<boolean>()
  // productsListData = signal<products[]>([]); // ← importante
  constructor(private service:MyPersonalGetService){
    toObservable(this.productItem)
      .pipe(
        switchMap(p => {
          if (!p?.id) {
            this.kardexList.set([]);
            return EMPTY; // no pidas nada si no hay producto
          }
          this.loading.set(true);
          this.error.set(null);
          return this.service.getKardexByProduct(p.id).pipe(
            catchError(err => {
              console.error(err);
              this.error.set('No se pudo cargar el kardex.');
              this.kardexList.set([]);
              return of([] as MovementKardex[]);
            }),
            finalize(() => this.loading.set(false))
          );
        }),
        takeUntilDestroyed()
      )
      .subscribe(rows => this.kardexList.set(rows ?? []));
  }

  onCancel(){
    this.closeModal.emit(false)
  }
}
