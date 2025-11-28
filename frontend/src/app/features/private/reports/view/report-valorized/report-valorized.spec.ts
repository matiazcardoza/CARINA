import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ReportValorized } from './report-valorized';

describe('ReportValorized', () => {
  let component: ReportValorized;
  let fixture: ComponentFixture<ReportValorized>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ReportValorized]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ReportValorized);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
