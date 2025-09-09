import { TestBed } from '@angular/core/testing';

import { FuelVouchersService } from './fuel-vouchers.service';

describe('FuelVouchersService', () => {
  let service: FuelVouchersService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(FuelVouchersService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
