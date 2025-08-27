import { TestBed } from '@angular/core/testing';

import { TestResquestsService } from './test-resquests.service';

describe('TestResquestsService', () => {
  let service: TestResquestsService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(TestResquestsService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
