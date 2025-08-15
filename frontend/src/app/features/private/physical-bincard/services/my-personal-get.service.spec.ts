import { TestBed } from '@angular/core/testing';

import { MyPersonalGetService } from './my-personal-get.service';

describe('MyPersonalGetService', () => {
  let service: MyPersonalGetService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(MyPersonalGetService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
