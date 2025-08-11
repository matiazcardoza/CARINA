import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DigitalSignatureWorkflow } from './digital-signature-workflow';

describe('DigitalSignatureWorkflow', () => {
  let component: DigitalSignatureWorkflow;
  let fixture: ComponentFixture<DigitalSignatureWorkflow>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DigitalSignatureWorkflow]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DigitalSignatureWorkflow);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
