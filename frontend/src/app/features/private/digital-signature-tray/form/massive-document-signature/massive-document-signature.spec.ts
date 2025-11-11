import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MassiveDocumentSignature } from './massive-document-signature';

describe('MassiveDocumentSignature', () => {
  let component: MassiveDocumentSignature;
  let fixture: ComponentFixture<MassiveDocumentSignature>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MassiveDocumentSignature]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MassiveDocumentSignature);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
