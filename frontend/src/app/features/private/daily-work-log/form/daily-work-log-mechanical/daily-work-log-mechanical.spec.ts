import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DailyWorkLogMechanical } from './daily-work-log-mechanical';

describe('DailyWorkLogMechanical', () => {
  let component: DailyWorkLogMechanical;
  let fixture: ComponentFixture<DailyWorkLogMechanical>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DailyWorkLogMechanical]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DailyWorkLogMechanical);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
