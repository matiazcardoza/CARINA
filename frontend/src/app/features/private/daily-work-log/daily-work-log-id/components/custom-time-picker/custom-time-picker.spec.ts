import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CustomTimePicker } from './custom-time-picker';

describe('CustomTimePicker', () => {
  let component: CustomTimePicker;
  let fixture: ComponentFixture<CustomTimePicker>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CustomTimePicker]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CustomTimePicker);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
