import { AfterViewInit, Component, ViewChild, OnInit, inject } from '@angular/core';
import { MatPaginator, MatPaginatorModule } from '@angular/material/paginator';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatTableDataSource, MatTableModule } from '@angular/material/table';
import { CommonModule } from '@angular/common';
import { DailyWorkLogService } from '../../../services/DailyWorkLogService/daily-work-log-service';
import { DailyWorkLogUpload } from './form/daily-work-log-upload/daily-work-log-upload';
import { DailyWorkLogReceive } from './form/daily-work-log-receive/daily-work-log-receive';
import { DailyWorkLogMechanical } from './form/daily-work-log-mechanical/daily-work-log-mechanical';
import { MatDialog } from '@angular/material/dialog';
import { ChangeDetectorRef } from '@angular/core';
import { Router } from '@angular/router';
import { HasPermissionDirective } from '../../../shared/directives/permission.directive';

export interface WorkLogElement {
  id: number;
  goal_id:number;
  description: string;
  goal_project: string;
  goal_detail: string;
  operator: string;
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
    MatButtonModule,
    HasPermissionDirective
  ],
  standalone: true,
})
export class DailyWorkLog implements AfterViewInit, OnInit {

  constructor(private cdr: ChangeDetectorRef) {}

  displayedColumns: string[] = ['id', 'description', 'state', 'actions'];
  dataSource = new MatTableDataSource<WorkLogElement>([]);

  private dailyWorkLogService = inject(DailyWorkLogService);
  private dialog = inject(MatDialog);
  private router = inject(Router);

  isLoading = false;  // Cambiar el estado inicial
  error: string | null = null;

  @ViewChild(MatPaginator) paginator!: MatPaginator;

  ngOnInit() {
    this.isLoading = false;
    this.error = null;
    this.cdr.detectChanges();

    Promise.resolve().then(() => {
      this.loadWorkLogData();
    });
  }

  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
  }

  loadWorkLogData() {
    Promise.resolve().then(() => {
      this.isLoading = true;
      this.error = null;
      this.cdr.detectChanges();

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
    });
  }

  reloadData() {
    Promise.resolve().then(() => {
      this.loadWorkLogData();
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
              this.loadWorkLogData();
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
      width: '95vw',
      maxWidth: '700px',
      height: '85vh'
    });

    dialogRef.afterClosed().subscribe((result) => {
      if (result) {
        Promise.resolve().then(() => {
          this.cdr.detectChanges();
          this.loadWorkLogData();
        });
      }
    });
  }

  openMechanicalDialog() {
    const dialogRef = this.dialog.open(DailyWorkLogMechanical, {
      width: '95vw',
      maxWidth: '700px',
      height: '85vh'
    });

    dialogRef.afterClosed().subscribe((result) => {
      if (result) {
        Promise.resolve().then(() => {
          this.cdr.detectChanges();
          this.loadWorkLogData();
        });
      }
    });
  }

  navigateToWorkLogId(id: number) {
    this.router.navigate(['/private/daily-work-log/daily-work-log-id', id]);
  }
}
