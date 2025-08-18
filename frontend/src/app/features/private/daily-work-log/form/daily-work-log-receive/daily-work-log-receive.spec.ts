import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DailyWorkLogReceive } from './daily-work-log-receive';

describe('DailyWorkLogReceive', () => {
  let component: DailyWorkLogReceive;
  let fixture: ComponentFixture<DailyWorkLogReceive>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DailyWorkLogReceive]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DailyWorkLogReceive);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
