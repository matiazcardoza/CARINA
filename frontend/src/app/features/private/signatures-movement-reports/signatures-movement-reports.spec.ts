import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SignaturesMovementReports } from './signatures-movement-reports';

describe('SignaturesMovementReports', () => {
  let component: SignaturesMovementReports;
  let fixture: ComponentFixture<SignaturesMovementReports>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [SignaturesMovementReports]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SignaturesMovementReports);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
