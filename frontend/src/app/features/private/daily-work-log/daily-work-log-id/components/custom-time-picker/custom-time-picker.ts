import { Component, Input, Output, EventEmitter, forwardRef, OnInit } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR, FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatSelectModule } from '@angular/material/select';
import { MatButtonModule } from '@angular/material/button';
import { MatInputModule } from '@angular/material/input';
import { MatIconModule } from '@angular/material/icon';
import { MatAutocompleteModule } from '@angular/material/autocomplete';

@Component({
  selector: 'app-custom-time-picker',
  templateUrl: './custom-time-picker.html',
  styleUrls: ['./custom-time-picker.css'],
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    MatFormFieldModule,
    MatSelectModule,
    MatButtonModule,
    MatInputModule,
    MatIconModule,
    MatAutocompleteModule
  ],
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => CustomTimePicker),
      multi: true
    }
  ]
})
export class CustomTimePicker implements ControlValueAccessor, OnInit {
  @Input() placeholder: string = 'HH:MM AM/PM';
  @Input() readonly: boolean = false;
  @Output() timeChange = new EventEmitter<string>();

  isOpen = false;
  hour: number = 12;
  minute: number = 0;
  period: 'AM' | 'PM' = 'AM';
  
  hours: number[] = Array.from({ length: 12 }, (_, i) => i + 1);
  minutes: number[] = Array.from({ length: 60 }, (_, i) => i);

  // Para búsqueda en autocomplete
  hourSearch: string = '12';
  minuteSearch: string = '00';
  filteredHours: number[] = [];
  filteredMinutes: number[] = [];

  // Para el input manual
  manualInput: string = '';

  private onChange: (value: string) => void = () => {};
  private onTouched: () => void = () => {};

  ngOnInit() {
    this.updateManualInput();
    this.filteredHours = [...this.hours];
    this.filteredMinutes = [...this.minutes];
    this.updateSearchFields();
  }

  writeValue(value: string): void {
    if (value) {
      this.parseTime(value);
      this.updateManualInput();
      this.updateSearchFields();
    }
  }

  registerOnChange(fn: any): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: any): void {
    this.onTouched = fn;
  }

  togglePicker() {
    if (!this.readonly) {
      this.isOpen = !this.isOpen;
      if (this.isOpen) {
        this.updateSearchFields();
        this.filteredHours = [...this.hours];
        this.filteredMinutes = [...this.minutes];
      } else {
        this.onTouched();
      }
    }
  }

  closePicker() {
    this.isOpen = false;
    this.onTouched();
  }

  filterHours(value: string) {
    const searchValue = value?.toString().toLowerCase() || '';
    
    if (searchValue === '') {
      this.filteredHours = [...this.hours];
      return;
    }

    this.filteredHours = this.hours.filter(h => 
      h.toString().includes(searchValue)
    );
  }

  filterMinutes(value: string) {
    const searchValue = value?.toString().toLowerCase() || '';
    
    if (searchValue === '') {
      this.filteredMinutes = [...this.minutes];
      return;
    }

    this.filteredMinutes = this.minutes.filter(m => {
      const displayValue = m < 10 ? '0' + m : m.toString();
      return displayValue.includes(searchValue);
    });
  }

  onHourSelected(value: number) {
    this.hour = value;
    this.hourSearch = value.toString();
    this.emitTimeChange();
  }

  onMinuteSelected(value: number) {
    this.minute = value;
    this.minuteSearch = value < 10 ? '0' + value : value.toString();
    this.emitTimeChange();
  }

  onHourChange(newHour: number) {
    this.hour = newHour;
    this.updateSearchFields();
    this.emitTimeChange();
  }

  onMinuteChange(newMinute: number) {
    this.minute = newMinute;
    this.updateSearchFields();
    this.emitTimeChange();
  }

  togglePeriod() {
    this.period = this.period === 'AM' ? 'PM' : 'AM';
    this.emitTimeChange();
  }

  public updateSearchFields() {
    this.hourSearch = this.hour.toString();
    this.minuteSearch = this.minute < 10 ? '0' + this.minute : this.minute.toString();
  }

  public emitTimeChange() {
    const timeString = this.formatTime();
    this.updateManualInput();
    this.updateSearchFields();
    this.onChange(timeString);
    this.timeChange.emit(timeString);
  }

  formatTime(): string {
    const h = this.hour.toString().padStart(2, '0');
    const m = this.minute.toString().padStart(2, '0');
    return `${h}:${m} ${this.period}`;
  }

  private updateManualInput() {
    this.manualInput = this.formatTime();
  }

  onManualInputChange(value: string) {
    // Validar y parsear el input manual
    const timeRegex = /^(0?[1-9]|1[0-2]):([0-5][0-9])\s?(AM|PM)$/i;
    const match = value.trim().match(timeRegex);
    
    if (match) {
      this.hour = parseInt(match[1]);
      this.minute = parseInt(match[2]);
      this.period = match[3].toUpperCase() as 'AM' | 'PM';
      this.emitTimeChange();
    }
  }

  onManualInputBlur() {
    // Si el input no es válido, restaurar el valor anterior
    const timeRegex = /^(0?[1-9]|1[0-2]):([0-5][0-9])\s?(AM|PM)$/i;
    if (!timeRegex.test(this.manualInput.trim())) {
      this.updateManualInput();
    }
    this.onTouched();
  }

  private parseTime(timeString: string) {
    // Formato esperado: "HH:MM AM/PM" o "HH:MM"
    const timeRegex12 = /^(0?[1-9]|1[0-2]):([0-5][0-9])\s?(AM|PM)$/i;
    const timeRegex24 = /^([0-1]?[0-9]|2[0-3]):([0-5][0-9])$/;
    
    const match12 = timeString.trim().match(timeRegex12);
    const match24 = timeString.trim().match(timeRegex24);
    
    if (match12) {
      this.hour = parseInt(match12[1]);
      this.minute = parseInt(match12[2]);
      this.period = match12[3].toUpperCase() as 'AM' | 'PM';
    } else if (match24) {
      // Convertir de 24h a 12h
      const hour24 = parseInt(match24[1]);
      this.minute = parseInt(match24[2]);
      
      if (hour24 === 0) {
        this.hour = 12;
        this.period = 'AM';
      } else if (hour24 < 12) {
        this.hour = hour24;
        this.period = 'AM';
      } else if (hour24 === 12) {
        this.hour = 12;
        this.period = 'PM';
      } else {
        this.hour = hour24 - 12;
        this.period = 'PM';
      }
    }
  }

  applyTime() {
    this.closePicker();
  }

  clearTime() {
    this.hour = 12;
    this.minute = 0;
    this.period = 'AM';
    this.updateSearchFields();
    this.emitTimeChange();
    this.closePicker();
  }
}