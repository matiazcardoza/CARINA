import { AfterViewInit, Component, ViewChild, OnInit, inject } from '@angular/core';
import { MatPaginator, MatPaginatorModule } from '@angular/material/paginator';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatTableDataSource, MatTableModule } from '@angular/material/table';
import { CommonModule } from '@angular/common';
import { DailyWorkLogService } from '../../../services/DailyWorkLogService/daily-work-log-service';
import { DailyWorkLogForm } from './form/daily-work-log-form/daily-work-log-form';
import { DailyWorkLogUpload } from './form/daily-work-log-upload/daily-work-log-upload';
import { DailyWorkLogReceive } from './form/daily-work-log-receive/daily-work-log-receive';
import { MatDialog } from '@angular/material/dialog';
import { ChangeDetectorRef } from '@angular/core';

import { Router } from '@angular/router';

export interface WorkLogElement {
  id: number;
  description: string;
  order_type: string;
  issue_date: string;
  state: number;
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

  displayedColumns: string[] = ['id', 'description', 'order_type', 'issue_date', 'state', 'actions'];
  dataSource = new MatTableDataSource<WorkLogElement>([]);
  
  private dailyWorkLogService = inject(DailyWorkLogService);
  private dialog = inject(MatDialog);
  private router = inject(Router);
  
  isLoading = true;
  error: string | null = null;

  @ViewChild(MatPaginator) paginator!: MatPaginator;

  ngOnInit() {
    this.loadWorkLogData();
  }

  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
  }

  loadWorkLogData() {
    this.isLoading = true;
    this.error = null;
    
    this.dailyWorkLogService.getOrdersWorkLogData()
      .subscribe({
        next: (data) => {
          this.dataSource.data = data;
          this.isLoading = false;
          this.cdr.detectChanges();
        },
        error: (error) => {
          this.error = 'Error al cargar los datos. Por favor, intenta nuevamente.';
          this.isLoading = false;
          this.cdr.detectChanges();
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

  openReceiveDialog() {
    const dialogRef = this.dialog.open(DailyWorkLogReceive, {
      width: '90vw',
      height: '85vh',
    });

    dialogRef.afterClosed().subscribe(() => {
      // Aquí puedes realizar cualquier acción después de cerrar el modal si es necesario
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

  navigateToWorkLogId(id: number) {
    this.router.navigate(['/carina/daily-work-log/daily-work-log-id', id]);
  }
}