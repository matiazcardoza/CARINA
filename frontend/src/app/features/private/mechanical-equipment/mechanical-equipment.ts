import { AfterViewInit, Component, ViewChild, OnInit, inject, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTableDataSource, MatTableModule } from '@angular/material/table';
import { MatPaginator, MatPaginatorModule } from '@angular/material/paginator';
import { MatDialog } from '@angular/material/dialog';

export interface MechanicalEquipmentElement {
  id: number;
  equipment_name: string;
  equipment_type: string;
  brand: string;
  model: string;
  serial_number: string;
  acquisition_date: string;
  status: string;
  last_maintenance: string;
}

@Component({
  selector: 'app-mechanical-equipment',
  standalone: true,
  imports: [
    CommonModule,
    MatButtonModule,
    MatIconModule,
    MatTableModule,
    MatPaginatorModule
  ],
  templateUrl: './mechanical-equipment.html',
  styleUrl: './mechanical-equipment.css'
})
export class MechanicalEquipment implements AfterViewInit, OnInit {
  
  displayedColumns: string[] = ['id', 'equipment_name', 'equipment_type', 'brand', 'model', 'status', 'actions'];
  dataSource = new MatTableDataSource<MechanicalEquipmentElement>([]);
  private dialog = inject(MatDialog);
  
  // Estado de carga inicial
  isLoading = false; 
  error: string | null = null;
  
  @ViewChild(MatPaginator) paginator!: MatPaginator;
  
  constructor(private cdr: ChangeDetectorRef) {}
  
  ngOnInit() {
    Promise.resolve().then(() => this.loadEquipmentData());
  }
  
  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
  }
  
  loadEquipmentData(): void {
    this.isLoading = true;
    this.error = null;
    this.cdr.detectChanges();
    
    // Simulando una llamada async con datos falsos
    setTimeout(() => {
      const fakeData: MechanicalEquipmentElement[] = [
        {
          id: 1,
          equipment_name: 'Excavadora Hidráulica',
          equipment_type: 'Maquinaria Pesada',
          brand: 'Caterpillar',
          model: '320D',
          serial_number: 'CAT320D001',
          acquisition_date: '2022-03-15',
          status: 'Operativo',
          last_maintenance: '2024-07-15'
        },
        {
          id: 2,
          equipment_name: 'Bulldozer',
          equipment_type: 'Maquinaria Pesada',
          brand: 'Komatsu',
          model: 'D65PX-18',
          serial_number: 'KOM65PX002',
          acquisition_date: '2021-11-20',
          status: 'Mantenimiento',
          last_maintenance: '2024-08-01'
        },
        {
          id: 3,
          equipment_name: 'Camión Volquete',
          equipment_type: 'Transporte',
          brand: 'Volvo',
          model: 'FMX 8x4',
          serial_number: 'VOL8X4003',
          acquisition_date: '2023-01-10',
          status: 'Operativo',
          last_maintenance: '2024-06-20'
        },
        {
          id: 4,
          equipment_name: 'Grúa Torre',
          equipment_type: 'Elevación',
          brand: 'Liebherr',
          model: '132 HC-L',
          serial_number: 'LIB132004',
          acquisition_date: '2022-08-05',
          status: 'Fuera de Servicio',
          last_maintenance: '2024-05-10'
        },
        {
          id: 5,
          equipment_name: 'Compactadora Vibrante',
          equipment_type: 'Compactación',
          brand: 'Dynapac',
          model: 'CA2500D',
          serial_number: 'DYN2500005',
          acquisition_date: '2023-06-12',
          status: 'Operativo',
          last_maintenance: '2024-08-10'
        },
        {
          id: 6,
          equipment_name: 'Retroexcavadora',
          equipment_type: 'Maquinaria Pesada',
          brand: 'JCB',
          model: '3CX ECO',
          serial_number: 'JCB3CX006',
          acquisition_date: '2022-12-03',
          status: 'Operativo',
          last_maintenance: '2024-07-25'
        }
      ];
      
      this.dataSource.data = fakeData;
      this.isLoading = false;
      this.cdr.detectChanges();
    }, 1500); // Simulando delay de red
  }
  
  reloadData() {
    Promise.resolve().then(() => this.loadEquipmentData());
  }
  
  openCreateDialog() {
    console.log('Abrir diálogo de creación');
    // Aquí irá la lógica para abrir el modal de creación
    // const dialogRef = this.dialog.open(MechanicalEquipmentForm, {
    //   width: '600px',
    //   data: { 
    //     isEdit: false,
    //     equipment: null
    //   }
    // });
    
    // dialogRef.afterClosed().subscribe(result => {
    //   if (result) {
    //     this.reloadData();
    //   }
    // });
  }
  
  openEditDialog(equipment: MechanicalEquipmentElement) {
    console.log('Editar equipo:', equipment);
    // Aquí irá la lógica para abrir el modal de edición
  }
  
  viewEquipmentDetails(equipment: MechanicalEquipmentElement) {
    console.log('Ver detalles del equipo:', equipment);
    // Aquí irá la lógica para ver los detalles
  }
  
  openMaintenanceDialog(equipment: MechanicalEquipmentElement) {
    console.log('Abrir mantenimiento para:', equipment);
    // Aquí irá la lógica para programar mantenimiento
  }
  
  deleteEquipment(id: number) {
    if (confirm('¿Estás seguro de que deseas eliminar este equipo?')) {
      Promise.resolve().then(() => {
        this.isLoading = true;
        this.cdr.detectChanges();
        
        // Simulando eliminación
        setTimeout(() => {
          this.dataSource.data = this.dataSource.data.filter(item => item.id !== id);
          this.isLoading = false;
          this.cdr.detectChanges();
        }, 1000);
      });
    }
  }
  
  generateEquipmentReport() {
    console.log('Generar reporte de equipos');
    // Aquí irá la lógica para generar reportes
  }
  
  getStatusClass(status: string): string {
    switch (status.toLowerCase()) {
      case 'operativo':
        return 'status-active';
      case 'mantenimiento':
        return 'status-maintenance';
      case 'fuera de servicio':
        return 'status-inactive';
      default:
        return 'status-unknown';
    }
  }
}