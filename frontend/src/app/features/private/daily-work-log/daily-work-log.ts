import { AfterViewInit, Component, ViewChild, OnInit, inject } from '@angular/core';
import { MatPaginator, MatPaginatorModule } from '@angular/material/paginator';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatTableDataSource, MatTableModule } from '@angular/material/table';
import { CommonModule } from '@angular/common';
import { DailyWorkLogService } from '../../../services/DailyWorkLogService/daily-work-log-service';
import { DailyWorkLogForm } from './form/daily-work-log-form/daily-work-log-form';
import { DailyWorkLogUpload } from './form/daily-work-log-upload/daily-work-log-upload';
import { MatDialog } from '@angular/material/dialog';
import { ChangeDetectorRef } from '@angular/core';

export interface WorkLogElement {
  id: number;
  work_date: string;
  start_time: string;
  initial_fuel: string;
}

@Component({
  selector: 'daily-work-log',
  styleUrl: 'daily-work-log.css',
  templateUrl: 'daily-work-log.html',
  imports: [
    MatTableModule,
    MatPaginatorModule,
    CommonModule,
    MatIconModule,
    MatButtonModule
  ],
  standalone: true,
})
export class DailyWorkLog implements AfterViewInit, OnInit {

  constructor(private cdr: ChangeDetectorRef) {}

  displayedColumns: string[] = ['id', 'work_date', 'start_time', 'initial_fuel', 'actions'];
  dataSource = new MatTableDataSource<WorkLogElement>([]);
  
  private dailyWorkLogService = inject(DailyWorkLogService);
  private dialog = inject(MatDialog);
  isLoading = false;
  error: string | null = null;

  @ViewChild(MatPaginator) paginator!: MatPaginator;

  ngOnInit() {
    this.loadWorkLogData();
  }

  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
  }

  loadWorkLogData() {
    this.error = null;
    
    this.dailyWorkLogService.getWorkLogData()
      .subscribe({
        next: (data) => {
          this.dataSource.data = data;
          this.isLoading = false;
        },
        error: (error) => {
          this.error = 'Error al cargar los datos. Por favor, intenta nuevamente.';
          this.isLoading = false;
        }
      });
  }

  reloadData() {
    this.loadWorkLogData();
  }

  openCreateDialog() {
    const dialogRef = this.dialog.open(DailyWorkLogForm, {
      width: '500px',
      data: { 
        isEdit: false,
        workLog: null 
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.loadWorkLogData();
      }
    });
  }

  openEditDialog(workLog: WorkLogElement) {
    const dialogRef = this.dialog.open(DailyWorkLogForm, {
      width: '500px',
      data: { 
        isEdit: true,
        workLog: workLog 
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        // Si se editó el registro, recargar los datos
        this.loadWorkLogData();
      }
    });
  }

  deleteWorkLog(id: number) {
    if (confirm('¿Estás seguro de que deseas eliminar este registro?')) {
      this.isLoading = true;
      this.dailyWorkLogService.deleteWorkLog(id)
        .subscribe({
          next: () => {
            this.isLoading = false;
            this.cdr.detectChanges();
            this.loadWorkLogData();
          },
          error: (error) => {
            this.isLoading = false;
            this.cdr.detectChanges();
            this.error = 'Error al eliminar el registro. Por favor, intenta nuevamente.';
          }
        });
    }
  }

  openCompleteModal(id: number) {
    const dialogRef = this.dialog.open(DailyWorkLogUpload, {
      width: '700px',
      maxWidth: '90vw',
      maxHeight: '90vh',
      data: { 
        isEdit: true,
        workLog: { id } as WorkLogElement
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.loadWorkLogData();
      }
    });
  }

  generatePdf(id: number) {
    this.dailyWorkLogService.generatePdf(id).subscribe({
        next: (response: Blob) => {
            const fileURL = URL.createObjectURL(response);
            window.open(fileURL, '_blank');
        },
        error: () => {
            this.error = 'Error al generar el PDF. Por favor, intenta nuevamente.';
        }
    });
}
}