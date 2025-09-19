import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DocumentSignature } from './document-signature';

describe('DailyWorkSignature', () => {
  let component: DocumentSignature;
  let fixture: ComponentFixture<DocumentSignature>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DocumentSignature]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DocumentSignature);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
