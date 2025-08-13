import { TestBed } from '@angular/core/testing';

import { MovementRegisterService } from './movement-register.service';

describe('MovementRegisterService', () => {
  let service: MovementRegisterService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(MovementRegisterService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
