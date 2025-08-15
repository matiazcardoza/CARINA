import { Component, output } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MyPersonalGetService } from '../../services/my-personal-get.service';
import { products } from '../../interface/products.interface';
import { ActivatedRoute, Router } from '@angular/router';

@Component({
  selector: 'app-add-new-product-modal',
  imports: [MatIconModule, ReactiveFormsModule],
  templateUrl: './add-new-product-modal.html',
  styleUrl: './add-new-product-modal.css'
})
export class AddNewProductModal {
  productCreated = output<any>()
  cancelar = output<void>()
  formularioProducto: FormGroup;

  constructor(private fb: FormBuilder, private productService: MyPersonalGetService, private route: ActivatedRoute ){

    this.formularioProducto = this.fb.group({
      name: ['', [Validators.required]],
      heritage_code:  ['', [Validators.required]],
      unit_price:  ['', [Validators.required]],
      state:  ['', [Validators.required]],
      // otros campos según Usuario (ejemplo: telefono, etc.)
    });
  }

  bincardId!: number;  // ID del usuario que se está editando
  onSubmit(): void {
    
    if (this.formularioProducto.valid) {
      
      const newProduct: products = this.formularioProducto.value;
      console.log("enviado datos", newProduct);
      this.bincardId = Number(this.route.snapshot.paramMap.get('bincardId'));
      // Llamar al servicio para crear el usuario en el backend
      this.productService.createProducts(this.bincardId,newProduct).subscribe({
        next: (usuarioCreado) => {
          // Emitir evento al padre con el usuario creado (respuesta del backend)
          this.productCreated.emit(usuarioCreado);
        },
        error: (e) => {
          console.error('Error al crear usuario', e);
          // Manejo de error (opcional: mostrar mensaje en el modal)
        },
        complete: () => {
          // Cerrar el modal automáticamente al completar
          this.cancelar.emit();
        }
      });
    }
  }

  onProductCreated(){
    // console.log("send data of new Prodct")
    this.productCreated.emit({
      name: "hola",
      other: "this is the oter date"
    })
  }
  onCancel(): void {
    // Emitir evento de cancelación para que el padre cierre el modal
    this.cancelar.emit();
  }

}
