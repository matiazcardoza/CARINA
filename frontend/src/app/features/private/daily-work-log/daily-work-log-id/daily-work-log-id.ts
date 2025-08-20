import { AfterViewInit, Component, ViewChild, OnInit, inject } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { DailyWorkLogService } from '../../../../services/DailyWorkLogService/daily-work-log-service';
import { MatTableDataSource, MatTableModule } from '@angular/material/table';
import { MatPaginator, MatPaginatorModule } from '@angular/material/paginator';
import { ChangeDetectorRef } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { DailyWorkLogForm } from '../form/daily-work-log-form/daily-work-log-form';
import { DailyWorkLogUpload } from '../form/daily-work-log-upload/daily-work-log-upload';

export interface WorkLogIdElement {
  id: number;
  work_date: string;
  start_time: string;
  initial_fuel: string;
  state: number;
}

@Component({
  selector: 'app-daily-work-log-id',
  standalone: true,
  imports: [
    CommonModule,
    MatButtonModule,
    MatIconModule,
    MatTableModule,
    MatPaginatorModule
  ],
  templateUrl: './daily-work-log-id.html',
  styleUrl: './daily-work-log-id.css'
})
export class DailyWorkLogId implements AfterViewInit, OnInit {
  
  workLogId: string | null = null;
  
  constructor(private route: ActivatedRoute, private cdr: ChangeDetectorRef) {}
  
  displayedColumns: string[] = ['id', 'work_date', 'start_time', 'initial_fuel', 'actions'];
  dataSource = new MatTableDataSource<WorkLogIdElement>([]);
  private dailyWorkLogService = inject(DailyWorkLogService);
  private dialog = inject(MatDialog);
  
  // Estado de carga inicial
  isLoading = false; 
  error: string | null = null;
  
  @ViewChild(MatPaginator) paginator!: MatPaginator;
  
  ngOnInit() {
    this.workLogId = this.route.snapshot.paramMap.get('id');
    Promise.resolve().then(() => this.loadWorkLogData());
  }
  
  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
  }
  
  loadWorkLogData(): void {
    this.isLoading = true;
    this.error = null;
    this.cdr.detectChanges();
    
    const workLogIdNumber = Number(this.workLogId);
    
    this.dailyWorkLogService.getWorkLogData(workLogIdNumber)
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
    Promise.resolve().then(() => this.loadWorkLogData());
  }
  
  openCreateDialog() {
    const dialogRef = this.dialog.open(DailyWorkLogForm, {
      width: '500px',
      data: { 
        isEdit: false,
        workLog: null,
        workLogId: this.workLogId
      }
    });
    
    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.reloadData();
      }
    });
  }
  
  openEditDialog(workLog: WorkLogIdElement) {
    const dialogRef = this.dialog.open(DailyWorkLogForm, {
      width: '500px',
      data: { 
        isEdit: true,
        workLog: workLog,
        workLogId: this.workLogId
      }
    });
    
    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.reloadData();
      }
    });
  }
  
  openCompleteModal(id: number) {
    const dialogRef = this.dialog.open(DailyWorkLogUpload, {
      width: '700px',
      maxWidth: '90vw',
      maxHeight: '90vh',
      data: { 
        isEdit: true,
        workLog: { id } as WorkLogIdElement
      }
    });
    
    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.reloadData();
      }
    });
  }
  
  deleteWorkLog(id: number) {
    if (confirm('¿Estás seguro de que deseas eliminar este registro?')) {
      Promise.resolve().then(() => {
        this.isLoading = true;
        this.cdr.detectChanges();
        
        this.dailyWorkLogService.deleteWorkLog(id)
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