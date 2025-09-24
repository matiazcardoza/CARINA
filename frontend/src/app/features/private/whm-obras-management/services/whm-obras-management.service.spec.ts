import { TestBed } from '@angular/core/testing';

import { WhmObrasManagementService } from './whm-obras-management.service';

describe('WhmObrasManagementService', () => {
  let service: WhmObrasManagementService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(WhmObrasManagementService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
