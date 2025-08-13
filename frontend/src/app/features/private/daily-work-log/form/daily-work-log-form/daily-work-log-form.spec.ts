import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DailyWorkLogForm } from './daily-work-log-form';

describe('DailyWorkLogForm', () => {
  let component: DailyWorkLogForm;
  let fixture: ComponentFixture<DailyWorkLogForm>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DailyWorkLogForm]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DailyWorkLogForm);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
