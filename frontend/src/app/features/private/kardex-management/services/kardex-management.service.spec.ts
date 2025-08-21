import { TestBed } from '@angular/core/testing';

import { KardexManagementService } from './kardex-management.service';

describe('KardexManagementService', () => {
  let service: KardexManagementService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(KardexManagementService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
