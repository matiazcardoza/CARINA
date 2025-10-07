import { AfterViewInit, Component, ViewChild, OnInit, inject, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTableDataSource, MatTableModule } from '@angular/material/table';
import { MatPaginator, MatPaginatorModule } from '@angular/material/paginator';
import { MatDialog } from '@angular/material/dialog';
import { MatTooltipModule } from '@angular/material/tooltip';

import { MechanicalEquipmentForm } from './form/mechanical-equipment-form/mechanical-equipment-form';
import { MechanicalEquipmentWork } from './form/mechanical-equipment-work/mechanical-equipment-work';
import { MechanicalEquipmentService } from '../../../services/MechanicalEquipmentService/mechanical-equipment-service';


export interface MechanicalEquipmentElement {
  id: number;
  machinery_equipment: string;
  ability: string;
  brand: string;
  model: string;
  plate: string;
  year: string;
  serial_number: string;
  state: number;
  state_service?: number;
}

@Component({
  selector: 'app-mechanical-equipment',
  standalone: true,
  imports: [
    CommonModule,
    MatButtonModule,
    MatIconModule,
    MatTableModule,
    MatPaginatorModule,
    MatTooltipModule
  ],
  templateUrl: './mechanical-equipment.html',
  styleUrl: './mechanical-equipment.css'
})
export class MechanicalEquipment implements AfterViewInit, OnInit {
  
  displayedColumns: string[] = ['id', 'machinery_equipment', 'ability', 'brand', 'model', 'plate', 'state', 'actions'];
  dataSource = new MatTableDataSource<MechanicalEquipmentElement>([]);

  private mechanicalEquipmentService = inject(MechanicalEquipmentService);
  private dialog = inject(MatDialog);
  
  // Estado de carga inicial
  isLoading = false; 
  error: string | null = null;
  
  @ViewChild(MatPaginator) paginator!: MatPaginator;
  
  constructor(private cdr: ChangeDetectorRef) {
    this.dataSource.filterPredicate = (data: MechanicalEquipmentElement, filter: string) => {
      const dataStr = (data.machinery_equipment + data.ability + data.brand + data.model + data.plate + data.serial_number + data.year).toLowerCase();
      return dataStr.indexOf(filter) !== -1;
    };
  }
  
  ngOnInit() {
    this.isLoading = false;
    this.error = null;
    this.cdr.detectChanges();
    
    Promise.resolve().then(() => {
      this.loadMechanicalEquipmentData();
    });
  }
  
  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
    this.cdr.detectChanges();
  }
  
  loadMechanicalEquipmentData(): void {
    Promise.resolve().then(() => {
      this.isLoading = true;
      this.error = null;
      this.cdr.detectChanges();
      
      this.mechanicalEquipmentService.getMechanicalEquipment()
        .subscribe({
          next: (data) => {
            this.dataSource.data = data;
            if (this.paginator) {
              this.dataSource.paginator = this.paginator;
              this.paginator.length = data.length;
            }
            this.isLoading = false;
            this.cdr.detectChanges();
          },
          error: (error) => {
            this.error = 'Error al cargar los datos. Por favor, intenta nuevamente.';
            this.isLoading = false;
            this.cdr.detectChanges();
          }
        });
    });
  }

  reloadData() {
    Promise.resolve().then(() => this.loadMechanicalEquipmentData());
  }
  
  openCreateDialog() {
    const dialogRef = this.dialog.open(MechanicalEquipmentForm, {
      width: '700px',
      data: { 
        isEdit: false,
        mechanicalEquipment: null
      }
    });
      
    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.reloadData();
      }
    });
  }
  
  openEditDialog(mechanicalEquipment: MechanicalEquipmentElement) {
    const dialogRef = this.dialog.open(MechanicalEquipmentForm, {
      width: '700px',
      data: { 
        isEdit: true,
        mechanicalEquipment: mechanicalEquipment
      }
    });
      
    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.reloadData();
      }
    });
  }
  
  reasignedWork(mechanicalEquipment: MechanicalEquipmentElement) {
    const dialogRef = this.dialog.open(MechanicalEquipmentWork, {
      width: '900px',
      data: {
        mechanicalEquipment: mechanicalEquipment
      }
    });
      
    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.reloadData();
      }
    });
  }
  
  openWorkDialog(mechanicalEquipment: MechanicalEquipmentElement) {
    const dialogRef = this.dialog.open(MechanicalEquipmentWork, {
      width: '900px',
      data: {
        mechanicalEquipment: mechanicalEquipment
      }
    });
      
    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.reloadData();
      }
    });
  }
  
  deleteMechanicalEquipment(id: number) {
    if (confirm('¿Estás seguro de que deseas eliminar este registro?')) {
      Promise.resolve().then(() => {
        this.isLoading = true;
        this.cdr.detectChanges();
        
        this.mechanicalEquipmentService.deleteMechanicalEquipment(id)
          .subscribe({
            next: () => {
              this.isLoading = false;
              this.cdr.detectChanges();
              this.reloadData();
            },
            error: (error) => {
              this.isLoading = false;
              this.error = 'Error al eliminar el registro. Por favor, intenta nuevamente.';
              this.cdr.detectChanges();
            }
          });
      });
    }
  }
  
  generateEquipmentReport() {
    console.log('Generar reporte de equipos');
    // Aquí irá la lógica para generar reportes
  }
  
  getStateClass(state: string | number): string {
    const stateNum = Number(state);
    switch (stateNum) {
      case 1:
        //operativo
        return 'status-active';
      case 2:
        //mantenimiento
        return 'status-maintenance';
      case 3:
        //inactivo
        return 'status-inactive';
      default:
        return 'status-unknown';
    }
  }

  applyFilter(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value;
    this.dataSource.filter = filterValue.trim().toLowerCase();

    if (this.dataSource.paginator) {
      this.dataSource.paginator.firstPage();
    }
  }
}