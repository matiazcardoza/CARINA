import { AfterViewInit, Component, ViewChild, OnInit, inject } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatInputModule } from '@angular/material/input';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatNativeDateModule } from '@angular/material/core';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { DailyWorkLogService } from '../../../../services/DailyWorkLogService/daily-work-log-service';
import { MatTableDataSource, MatTableModule } from '@angular/material/table';
import { MatPaginator, MatPaginatorModule } from '@angular/material/paginator';
import { ChangeDetectorRef } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { DailyWorkLogForm } from './form/daily-work-log-form/daily-work-log-form';
import { DailyWorkLogUpload } from './form/daily-work-log-upload/daily-work-log-upload';
import { DailyWorkSignature } from './form/daily-work-signature/daily-work-signature';
import { startWith } from 'rxjs/operators';
import { ShiftsService } from '../../../../services/ShiftsService/shifts-service';
import { MatButtonToggleModule } from '@angular/material/button-toggle';

import { HasPermissionDirective, HasRoleDirective } from '../../../../shared/directives/permission.directive';

export interface WorkLogIdElement {
  id: number;
  work_date: string;
  start_time: string;
  initial_fuel: string;
  gasolina?: number;
  end_time: string;
  occurrences: string;
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
    MatPaginatorModule,
    MatDatepickerModule,
    MatInputModule,
    MatFormFieldModule,
    MatNativeDateModule,
    ReactiveFormsModule,
    HasPermissionDirective,
    HasRoleDirective,
    MatButtonToggleModule
],
  templateUrl: './daily-work-log-id.html',
  styleUrl: './daily-work-log-id.css'
})
export class DailyWorkLogId implements AfterViewInit, OnInit {

  serviceId: string | null = null;
  serviceState: string | null = null;

  dateControl = new FormControl(new Date());
  selectedDate: string = this.formatDate(new Date());
  selectedShift: number | 'all' = 'all';

  shiftsData: any[] = [];

  constructor(private route: ActivatedRoute, private cdr: ChangeDetectorRef) {}

  displayedColumns: string[] = ['id', 'description', 'work_date', 'start_time', 'initial_fuel', 'end_time', 'actions'];
  dataSource = new MatTableDataSource<WorkLogIdElement>([]);
  private dailyWorkLogService = inject(DailyWorkLogService);
  private shiftsService = inject(ShiftsService);
  private dialog = inject(MatDialog);

  // Estado de carga inicial
  isLoading = false;
  error: string | null = null;

  @ViewChild(MatPaginator) paginator!: MatPaginator;

  ngOnInit() {
  this.serviceId = this.route.snapshot.paramMap.get('id');
  this.serviceState = this.route.snapshot.paramMap.get('state');

  this.dateControl.valueChanges
    .pipe(
      // startWith() emite el valor inicial del FormControl al iniciar la suscripción
      startWith(this.dateControl.value)
    )
    .subscribe(date => {
      if (date) {
        this.selectedDate = this.formatDate(date);
        this.loadWorkLogData();
        this.loadShifts();
      }
    });
  }

  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
  }

  setShift(shiftId: number | 'all') {
    this.selectedShift = shiftId;
    this.loadWorkLogData();
  }

  get allRecordsState2(): boolean {
    return this.dataSource.data.length > 0 &&
           this.dataSource.data.every(record => record.state === 2);
  }

  get allRecordsState3(): boolean {
    return this.dataSource.data.length > 0 &&
           this.dataSource.data.every(record => record.state === 3);
  }

  get showCreateButton(): boolean {
    return !this.allRecordsState3 && this.allRecordsState2;
  }

  get showPdfButton(): boolean {
    return this.allRecordsState2 && !this.allRecordsState3;
  }

  get showSignatureButton(): boolean {
    return this.allRecordsState3;
  }

  private loadShifts() {
    this.shiftsService.getShifts().subscribe({
      next: (data) => {
        console.log('turnos cargados:', data);
        this.shiftsData = data;
        this.cdr.detectChanges();
      },
      error: (error) => {
        console.error('Error al cargar turnos:', error);
        this.cdr.detectChanges();
      }
    });
  }

  loadWorkLogData(): void {
    this.isLoading = true;
    this.error = null;
    this.cdr.detectChanges();

    const serviceIdNumber = Number(this.serviceId);

    this.dailyWorkLogService.getWorkLogData(serviceIdNumber, this.selectedDate, this.selectedShift)
      .subscribe({
        next: (data) => {
          console.log(data);
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

  private formatDate(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  goToToday() {
    const today = new Date();
    this.dateControl.setValue(today);
    // El loadWorkLogData se ejecutará automáticamente por la suscripción
  }

  goToPreviousDay() {
    const currentDate = this.dateControl.value || new Date();
    const previousDay = new Date(currentDate);
    previousDay.setDate(previousDay.getDate() - 1);
    this.dateControl.setValue(previousDay);
  }

  goToNextDay() {
    const currentDate = this.dateControl.value || new Date();
    const nextDay = new Date(currentDate);
    nextDay.setDate(nextDay.getDate() + 1);
    this.dateControl.setValue(nextDay);
  }

  reloadData() {
    Promise.resolve().then(() => this.loadWorkLogData());
  }

  openCreateDialog() {
    const dialogRef = this.dialog.open(DailyWorkLogForm, {
      width: '900px',
      data: {
        isEdit: false,
        workLog: null,
        serviceId: this.serviceId,
        serviceState: this.serviceState,
        selectedDateFromFilter: this.dateControl.value,
        selectedShift: this.selectedShift
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
        serviceId: this.serviceId,
        serviceState: this.serviceState
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
        workLog: { id } as WorkLogIdElement,
        serviceId: this.serviceId
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.reloadData();
      }
    });
  }

  signaturePdf(serviceId: number) {
    const dialogRef = this.dialog.open(DailyWorkSignature, {
      width: '100vw',
      height: '100vh',
      maxWidth: '100vw',
      maxHeight: '100vh',
      panelClass: ['maximized-dialog-panel', 'no-scroll-dialog'],
      disableClose: false,
      hasBackdrop: true,
      backdropClass: 'maximized-dialog-backdrop',
      autoFocus: false,
      restoreFocus: false,
      data: {
        serviceId: serviceId,
        date: this.selectedDate,
        shift: this.selectedShift
      }
    });

    setTimeout(() => {
      const body = document.body;
      const html = document.documentElement;
      body.style.overflow = 'hidden';
      html.style.overflow = 'hidden';
    }, 0);

    dialogRef.afterClosed().subscribe(result => {
      const body = document.body;
      const html = document.documentElement;
      body.style.overflow = '';
      html.style.overflow = '';

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

  convertWorkLogId(id: any): number {
    return Number(id);
  }

  generatePdf(id: number) {
    /*this.dailyWorkLogService.generatePdf(id, this.selectedDate).subscribe({
      next: (response: Blob) => {
        const fileURL = URL.createObjectURL(response);
        window.open(fileURL, '_blank');
      },
      error: () => {
        this.error = 'Error al generar el PDF. Por favor, intenta nuevamente.';
      }
    });*/
    console.log(this.selectedDate);

    this.dailyWorkLogService.generatePdf(id, this.selectedDate, this.selectedShift)
      .subscribe({
        next: (responde) => {
          console.log(responde);
          this.isLoading = false;
          this.cdr.detectChanges();
          this.reloadData();
        },
        error: (error) => {
          this.isLoading = false;
          this.cdr.detectChanges();
          console.error('Error al actualizar:', error);
        }
    });
  }
}
