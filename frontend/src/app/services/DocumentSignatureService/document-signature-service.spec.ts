import { TestBed } from '@angular/core/testing';

import { DocumentSignatureService } from './document-signature-service';

describe('DocumentSignatureService', () => {
  let service: DocumentSignatureService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(DocumentSignatureService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
