import { AfterViewInit, Component, ViewChild, OnInit, inject } from '@angular/core';
import { MatPaginator, MatPaginatorModule } from '@angular/material/paginator';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatTableDataSource, MatTableModule } from '@angular/material/table';
import { CommonModule } from '@angular/common';
import { DailyWorkLogService } from '../../../services/DailyWorkLogService/daily-work-log-service';
import { DailyWorkLogForm } from './form/daily-work-log-form/daily-work-log-form';
import { MatDialog } from '@angular/material/dialog';

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
  displayedColumns: string[] = ['id', 'work_date', 'start_time', 'initial_fuel'];
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
  }
}