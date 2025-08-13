import { TestBed } from '@angular/core/testing';

import { ExmapleService } from './exmaple.service';

describe('ExmapleService', () => {
  let service: ExmapleService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(ExmapleService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
