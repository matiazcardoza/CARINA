import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
// import { DemoService } from './demo.service';
import { RenderingTestService } from './services/rendering-test.service';
import { WhmKardexManagementService } from '../../../features/private/whm-kardex-management/services/whm-kardex-management.service';
@Component({
  selector: 'app-rendering-test',
  imports: [CommonModule, FormsModule],
  templateUrl: './rendering-test.html',
  styleUrl: './rendering-test.css'
})
export class RenderingTest {
  // value = signal(null); 
  // secondValue = "hola mundo";
  // ngOnInit() {
  //   // this.secondValue = "hello world";
  // }
  // ngAfterViewInit() {
  //   // ⚠️ Cambiar un binding aquí provoca NG0100 en dev mode
  //   // this.secondValue = "hello world";
  // }



 obras = [1, 2, 3];
  selectedObraId: number | null = null;

  // === bindings que la vista lee ===
  loadingOrdenes = false;    // <<— este boolean es el que "salta" (true → false)
  ordenes: any[] = [];       //     y este array pasa de [] a [{…}] en el mismo ciclo

  constructor(private api: RenderingTestService) {}

  onObraChange(obraId: number | null) {
    this.selectedObraId = obraId;
    this.cargarOrdenes();
  }

  cargarOrdenes() {
    if (!this.selectedObraId) { this.ordenes = []; return; }

    this.loadingOrdenes = true; // la vista lo lee como 'true' en la 1ª pasada
    this.api.getOrdenesCompra(this.selectedObraId, '').subscribe({
      next: rows => { this.ordenes = rows ?? []; }, // [] → [{…}]
      complete: () => { this.loadingOrdenes = false; } // ¡true → false en el MISMO ciclo!
    });
  }
}

