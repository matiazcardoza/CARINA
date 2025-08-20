import { Component, signal } from '@angular/core';
import { MyPersonalGetService } from './services/my-personal-get.service';
import { Order } from './interface/order.interface';
import { ActivatedRoute, Router } from '@angular/router';
import { OrdersSilucia } from './interface/orders-silucia.interface';
@Component({
  selector: 'app-physical-bincard',
  imports: [],
  templateUrl: './physical-bincard.html',
  styleUrl: './physical-bincard.css'
})
export class PhysicalBincard {
  // data = [
  //   { id: 1, nombre: 'Juan Pérez', correo: 'juanp@example.com', pais: 'Perú', estado: 'Activo' },
  //   { id: 2, nombre: 'María López', correo: 'marial@example.com', pais: 'México', estado: 'Inactivo' },
  //   { id: 3, nombre: 'Carlos Sánchez', correo: 'csanchez@example.com', pais: 'Chile', estado: 'Activo' },
  //   { id: 4, nombre: 'Ana Torres', correo: 'ana.t@example.com', pais: 'Argentina', estado: 'Pendiente' },
  //   { id: 5, nombre: 'Luis Romero', correo: 'luisr@example.com', pais: 'Colombia', estado: 'Activo' }
  // ];

  myData = signal<Order[]>([]); // ← importante
  constructor(
    private service:MyPersonalGetService,
    private router: Router,
    private route: ActivatedRoute 
  ){
    // bincardId!
    
  }


  getData(){
    this.service.getData().subscribe({
      // next: (value:Order[]) => {
      next: (value:OrdersSilucia) => {
        this.myData.set(value.data)
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
    this.getData()
  }
  seeData(): void{
    console.log(this.myData());
  }

  // Navegar a otra pagina
  // Navegar a la página de creación de usuario
  // nuevoUsuario(): void {
  //   this.router.navigate(['/usuarios/crear']);
  // }
  seeProductos(bincardId:number): void {
    // editarUsuario(id: number): void {
    // this.router.navigate(['1/products']);
      // this.router.navigate(['1', 'products'], { relativeTo: this.route });
      // this.router.navigate(['/usuarios/editar', id]);
      this.router.navigate([bincardId, 'products'], { relativeTo: this.route });

  }

  // Navegar a la página de edición de un usuario existente
  // editarUsuario(id: number): void {
  //   this.router.navigate(['/usuarios/editar', id]);
  // }
}


