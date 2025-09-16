import { TestBed } from '@angular/core/testing';

import { ReportsServicesService } from './reports-services-service';

describe('ReportsServicesService', () => {
  let service: ReportsServicesService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(ReportsServicesService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
