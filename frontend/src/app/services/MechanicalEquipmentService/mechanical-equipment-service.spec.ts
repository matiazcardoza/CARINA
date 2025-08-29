import { TestBed } from '@angular/core/testing';

import { MechanicalEquipmentService } from './mechanical-equipment-service';

describe('MechanicalEquipmentService', () => {
  let service: MechanicalEquipmentService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(MechanicalEquipmentService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
