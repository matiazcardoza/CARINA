import { TestBed } from '@angular/core/testing';

import { WhmUserManagementService } from './whm-user-management.service';

describe('WhmUserManagementService', () => {
  let service: WhmUserManagementService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(WhmUserManagementService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
