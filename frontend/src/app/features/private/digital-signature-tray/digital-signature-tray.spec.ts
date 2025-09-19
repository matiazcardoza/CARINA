import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DigitalSignatureTray } from './digital-signature-tray';

describe('DigitalSignatureWorkflow', () => {
  let component: DigitalSignatureTray;
  let fixture: ComponentFixture<DigitalSignatureTray>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DigitalSignatureTray]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DigitalSignatureTray);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
