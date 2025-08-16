import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DailyWorkLogId } from './daily-work-log-id';

describe('DailyWorkLogId', () => {
  let component: DailyWorkLogId;
  let fixture: ComponentFixture<DailyWorkLogId>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DailyWorkLogId]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DailyWorkLogId);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
