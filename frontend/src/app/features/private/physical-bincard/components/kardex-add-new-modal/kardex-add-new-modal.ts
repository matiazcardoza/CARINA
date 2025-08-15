import { Component, input, output } from '@angular/core';
import { products } from '../../interface/products.interface';
import { MatIconModule } from '@angular/material/icon';

@Component({
  selector: 'app-kardex-add-new-modal',
  imports: [MatIconModule],
  templateUrl: './kardex-add-new-modal.html',
  styleUrl: './kardex-add-new-modal.css'
})
export class KardexAddNewModal {
  productItem = input<products>();
  cancelar = output<void>()
  onProductCreated(){
    console.log(this.productItem);
  }
  onCancel(){
    console.log("cerrar");
    this.cancelar.emit()
  }
}
