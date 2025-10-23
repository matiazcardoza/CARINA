import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DailyWorkLogUpload } from './daily-work-log-upload';

describe('DailyWorkLogUpload', () => {
  let component: DailyWorkLogUpload;
  let fixture: ComponentFixture<DailyWorkLogUpload>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DailyWorkLogUpload]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DailyWorkLogUpload);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
