import { Component, input, output } from '@angular/core';
import { products } from '../../interface/products.interface';
import { MatIconModule } from '@angular/material/icon';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MovementKardex } from '../../interface/movement-kardex.interface';
import { MyPersonalGetService } from '../../services/my-personal-get.service';

type Movimiento = 'entrada' | 'salida';


@Component({
  selector: 'app-kardex-add-new-modal',
  imports: [MatIconModule, ReactiveFormsModule],
  templateUrl: './kardex-add-new-modal.html',
  styleUrl: './kardex-add-new-modal.css'
})
export class KardexAddNewModal {
  productItem = input<products>();
  cancelar = output<void>()
  formularioKardex: FormGroup

  constructor(private fb: FormBuilder,private kardexService: MyPersonalGetService){

    this.formularioKardex = this.fb.group({
      movement_type: ['', [Validators.required]],
      amount:  ['', [Validators.required]],
    });

  }

  onSubmit():void{
    const prod = this.productItem();
    if (!prod?.id) { console.error('productItem no disponible'); return; }
    
    const raw = this.formularioKardex.value as { movement_type: Movimiento; amount: string | number };
    const payload: Pick<MovementKardex, 'movement_type' | 'amount'> = {
      movement_type: (raw.movement_type || '').toLowerCase() as Movimiento,
      amount: Number(raw.amount),
    };

    // if (!['entrada', 'salida'].includes(payload.movement_type) || Number.isNaN(payload.amount)) {
    //   console.error('Datos inválidos', payload);
    //   return;
    // }

    if(this.formularioKardex.valid){
      const newKardexMovement: MovementKardex = this.formularioKardex.value;
      // productItem.id 
      this.kardexService.createKardexMovement(prod.id,newKardexMovement).subscribe({
          next: (usuarioCreado) => {
          // Emitir evento al padre con el usuario creado (respuesta del backend)
            // this.productCreated.emit(usuarioCreado);
          },
          error: (e) => {
            console.error('Error al crear usuario', e);
            // Manejo de error (opcional: mostrar mensaje en el modal)
          },
          complete: () => {
            // Cerrar el modal automáticamente al completar
            this.cancelar.emit();
          }
      })
    }
  }

  onProductCreated(){
    console.log(this.productItem());
  }
  onCancel(){
    console.log("cerrar");
    this.cancelar.emit()
  }
}
