import { TestBed } from '@angular/core/testing';

import { DailyWorkLogService } from './daily-work-log-service';

describe('DailyWorkLogService', () => {
  let service: DailyWorkLogService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(DailyWorkLogService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
