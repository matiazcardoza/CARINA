import { TestBed } from '@angular/core/testing';

import { WhmKardexManagementService } from './whm-kardex-management.service';

describe('WhmKardexManagementService', () => {
  let service: WhmKardexManagementService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(WhmKardexManagementService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
