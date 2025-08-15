import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatButtonModule } from '@angular/material/button';
import { MatTableModule } from '@angular/material/table';
import { MatIconModule } from '@angular/material/icon';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatNativeDateModule } from '@angular/material/core';
import { MatSnackBarModule, MatSnackBar } from '@angular/material/snack-bar';
import { MatDialogModule, MatDialog } from '@angular/material/dialog';

interface ParteDiario {
  id_parte: number;
  id_servicio: number;
  nombre_servicio: string;
  fecha_parte: Date;
  hora_inicio: string;
  hora_fin: string;
  combustible_inicial: number;
  combustible_final: number;
  combustible_consumido: number;
  horas_trabajadas: number;
  estado: string;
}

interface ServicioMaquinaria {
  id_servicio: number;
  nombre: string;
  tipo_maquinaria: string;
  codigo: string;
}

@Component({
  selector: 'app-fuel-control',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    MatCardModule,
    MatFormFieldModule,
    MatInputModule,
    MatSelectModule,
    MatButtonModule,
    MatTableModule,
    MatIconModule,
    MatDatepickerModule,
    MatNativeDateModule,
    MatSnackBarModule,
    MatDialogModule
  ],
  templateUrl: './fuel-control.html',
  styleUrl: './fuel-control.css'
})
export class FuelControl {
  
  // Datos falsos para servicios de maquinaria seca
  serviciosMaquinaria: ServicioMaquinaria[] = [
    { id_servicio: 1, nombre: 'Excavadora CAT 320', tipo_maquinaria: 'Excavadora', codigo: 'EXC-001' },
    { id_servicio: 2, nombre: 'Bulldozer CAT D6', tipo_maquinaria: 'Bulldozer', codigo: 'BLD-002' },
    { id_servicio: 3, nombre: 'Cargador Frontal 950', tipo_maquinaria: 'Cargador', codigo: 'CFR-003' },
    { id_servicio: 4, nombre: 'Motoniveladora 140M', tipo_maquinaria: 'Motoniveladora', codigo: 'MNV-004' },
    { id_servicio: 5, nombre: 'Compactadora Vibrante', tipo_maquinaria: 'Compactadora', codigo: 'CPV-005' }
  ];

  // Datos falsos para partes diarios con combustible
  partesDiarios: ParteDiario[] = [
    {
      id_parte: 1,
      id_servicio: 1,
      nombre_servicio: 'Excavadora CAT 320',
      fecha_parte: new Date('2024-08-15'),
      hora_inicio: '08:00',
      hora_fin: '17:00',
      combustible_inicial: 150.5,
      combustible_final: 85.2,
      combustible_consumido: 65.3,
      horas_trabajadas: 9,
      estado: 'Pendiente'
    },
    {
      id_parte: 2,
      id_servicio: 2,
      nombre_servicio: 'Bulldozer CAT D6',
      fecha_parte: new Date('2024-08-15'),
      hora_inicio: '07:30',
      hora_fin: '16:30',
      combustible_inicial: 200.0,
      combustible_final: 120.5,
      combustible_consumido: 79.5,
      horas_trabajadas: 9,
      estado: 'Actualizado'
    },
    {
      id_parte: 3,
      id_servicio: 3,
      nombre_servicio: 'Cargador Frontal 950',
      fecha_parte: new Date('2024-08-14'),
      hora_inicio: '08:15',
      hora_fin: '17:15',
      combustible_inicial: 180.8,
      combustible_final: 95.3,
      combustible_consumido: 85.5,
      horas_trabajadas: 9,
      estado: 'Pendiente'
    },
    {
      id_parte: 4,
      id_servicio: 4,
      nombre_servicio: 'Motoniveladora 140M',
      fecha_parte: new Date('2024-08-14'),
      hora_inicio: '09:00',
      hora_fin: '18:00',
      combustible_inicial: 160.2,
      combustible_final: 78.9,
      combustible_consumido: 81.3,
      horas_trabajadas: 9,
      estado: 'Actualizado'
    },
    {
      id_parte: 5,
      id_servicio: 5,
      nombre_servicio: 'Compactadora Vibrante',
      fecha_parte: new Date('2024-08-13'),
      hora_inicio: '08:30',
      hora_fin: '17:30',
      combustible_inicial: 120.0,
      combustible_final: 65.7,
      combustible_consumido: 54.3,
      horas_trabajadas: 9,
      estado: 'Pendiente'
    }
  ];

  // Columnas para la tabla
  displayedColumns: string[] = [
    'codigo',
    'nombre_servicio',
    'fecha_parte',
    'horas_trabajadas',
    'combustible_inicial',
    'combustible_final',
    'combustible_consumido',
    'estado',
    'acciones'
  ];

  // Formulario para actualización de combustible
  selectedParte: ParteDiario | null = null;
  combustibleInicialTemp: number = 0;
  combustibleFinalTemp: number = 0;

  constructor(
    private snackBar: MatSnackBar,
    private dialog: MatDialog
  ) {}

  // Seleccionar parte para editar combustible
  editarCombustible(parte: ParteDiario): void {
    this.selectedParte = { ...parte };
    this.combustibleInicialTemp = parte.combustible_inicial;
    this.combustibleFinalTemp = parte.combustible_final;
  }

  // Cancelar edición
  cancelarEdicion(): void {
    this.selectedParte = null;
    this.combustibleInicialTemp = 0;
    this.combustibleFinalTemp = 0;
  }

  // Actualizar combustible
  actualizarCombustible(): void {
    if (this.selectedParte && this.combustibleFinalTemp <= this.combustibleInicialTemp) {
      // Buscar el índice en el array
      const index = this.partesDiarios.findIndex(p => p.id_parte === this.selectedParte!.id_parte);
      
      if (index !== -1) {
        // Actualizar valores
        this.partesDiarios[index].combustible_inicial = this.combustibleInicialTemp;
        this.partesDiarios[index].combustible_final = this.combustibleFinalTemp;
        this.partesDiarios[index].combustible_consumido = 
          this.combustibleInicialTemp - this.combustibleFinalTemp;
        this.partesDiarios[index].estado = 'Actualizado';

        this.snackBar.open('Combustible actualizado correctamente', 'Cerrar', {
          duration: 3000,
          horizontalPosition: 'right',
          verticalPosition: 'top'
        });

        this.cancelarEdicion();
      }
    } else {
      this.snackBar.open('Error: El combustible final debe ser menor al inicial', 'Cerrar', {
        duration: 3000,
        horizontalPosition: 'right',
        verticalPosition: 'top'
      });
    }
  }

  // Validar que el combustible final no sea mayor al inicial
  validarCombustible(): boolean {
    return this.combustibleFinalTemp <= this.combustibleInicialTemp && 
           this.combustibleFinalTemp >= 0 && 
           this.combustibleInicialTemp > 0;
  }

  // Obtener el código de servicio
  getCodigoServicio(id_servicio: number): string {
    const servicio = this.serviciosMaquinaria.find(s => s.id_servicio === id_servicio);
    return servicio ? servicio.codigo : 'N/A';
  }

  // Calcular consumo promedio por hora
  getConsumoPromedio(parte: ParteDiario): number {
    if (parte.horas_trabajadas > 0) {
      return Number((parte.combustible_consumido / parte.horas_trabajadas).toFixed(2));
    }
    return 0;
  }

  // Filtrar por estado
  filtrarPorEstado(estado: string): ParteDiario[] {
    if (estado === 'todos') {
      return this.partesDiarios;
    }
    return this.partesDiarios.filter(p => p.estado.toLowerCase() === estado.toLowerCase());
  }

  // Obtener total de combustible consumido
  getTotalCombustibleConsumido(): number {
    return this.partesDiarios.reduce((total, parte) => total + parte.combustible_consumido, 0);
  }

  // Obtener promedio de consumo
  getPromedioCombustible(): number {
    const total = this.getTotalCombustibleConsumido();
    return this.partesDiarios.length > 0 ? Number((total / this.partesDiarios.length).toFixed(2)) : 0;
  }
}