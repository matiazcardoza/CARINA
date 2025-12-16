import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ReportAddDeductives } from './report-add-deductives';

describe('ReportAddDeductives', () => {
  let component: ReportAddDeductives;
  let fixture: ComponentFixture<ReportAddDeductives>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ReportAddDeductives]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ReportAddDeductives);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
