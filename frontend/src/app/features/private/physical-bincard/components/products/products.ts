import { Component, signal } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { MyPersonalGetService } from '../../services/my-personal-get.service';
import { products } from '../../interface/products.interface';
import { AddNewProductModal } from '../add-new-product-modal/add-new-product-modal';
import { KardexListModal } from '../kardex-list-modal/kardex-list-modal';
import { KardexAddNewModal } from '../kardex-add-new-modal/kardex-add-new-modal';
@Component({
  selector: 'app-products',
  imports: [AddNewProductModal,KardexListModal, KardexAddNewModal],
  templateUrl: './products.html',
  styleUrl: './products.css'
})
export class Products {
  productsListData = signal<products[]>([]); // ← importante
  isModalOpen = signal<boolean>(false);
  isModalKardexListOpen = signal<boolean>(false);
  isModalKardexLAddNewOpen = signal<boolean>(false);
  // productSelected = signal<any>({});
  productSelected = signal<products>( {
    id: 0,
    order_id: 0,
    name: '',
    heritage_code: 'sdf34234',
    unit_price: '', // ← viene como string, no como número
    state: 1,
  }
);
  // productSelected = signal<Product | null>(null);
  constructor(
    private service:MyPersonalGetService,
    private router: Router,
    private route: ActivatedRoute 
  ){

  }
  usuarioId!: number;  // ID del usuario que se está editando
  getProducts(){
      this.usuarioId = Number(this.route.snapshot.paramMap.get('bincardId'));
      this.service.getProducts(this.usuarioId).subscribe({
        next: (value:products[]) => {
          this.productsListData.set(value)
          // this.isOpen.set(value) 
          console.log("this is the value of api", value)
        },
        error: err => {
          console.warn('An error ocurred: ', err)
        },
        complete: () => {
          console.log("the request finished")
        }
      })
  }

  ngOnInit(): void {
    this.getProducts()
  }

  cerrarModalCrear():void{
    this.isModalOpen.update( v => !v)
  }
  abrirModalCrear():void{
    this.isModalOpen.set(true)
  }
  onProductCreated(value: any):void{
    console.log("this date coming of add-new-product ", value);
    this.getProducts(); 
  }
  selectProduct(product:products):void{
    console.log("producto seleccionado: ", product)
    this.isModalKardexListOpen.set(true);
    this.productSelected.set(product);
  }
  cerrarModalListaKardex():void{
    this.isModalKardexListOpen.set(false)
  }
  addNewKardexItem():void{
    this.isModalKardexLAddNewOpen.set(true)
  }
  cerrarKardexAddNewModal():void{
    this.isModalKardexLAddNewOpen.set(false);
  }
}
