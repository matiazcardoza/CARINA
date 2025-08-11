import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DailyWorkLog } from './daily-work-log';

describe('DailyWorkLog', () => {
  let component: DailyWorkLog;
  let fixture: ComponentFixture<DailyWorkLog>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DailyWorkLog]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DailyWorkLog);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
