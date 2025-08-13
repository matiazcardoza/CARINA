import { Component } from '@angular/core';

@Component({
  selector: 'app-physical-bincard',
  imports: [],
  templateUrl: './physical-bincard.html',
  styleUrl: './physical-bincard.css'
})
export class PhysicalBincard {
  data = [
    { id: 1, nombre: 'Juan Pérez', correo: 'juanp@example.com', pais: 'Perú', estado: 'Activo' },
    { id: 2, nombre: 'María López', correo: 'marial@example.com', pais: 'México', estado: 'Inactivo' },
    { id: 3, nombre: 'Carlos Sánchez', correo: 'csanchez@example.com', pais: 'Chile', estado: 'Activo' },
    { id: 4, nombre: 'Ana Torres', correo: 'ana.t@example.com', pais: 'Argentina', estado: 'Pendiente' },
    { id: 5, nombre: 'Luis Romero', correo: 'luisr@example.com', pais: 'Colombia', estado: 'Activo' }
  ];
}
