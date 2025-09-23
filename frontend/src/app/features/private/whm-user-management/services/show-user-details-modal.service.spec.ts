import { TestBed } from '@angular/core/testing';

import { ShowUserDetailsModalService } from './show-user-details-modal.service';

describe('ShowUserDetailsModalService', () => {
  let service: ShowUserDetailsModalService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(ShowUserDetailsModalService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
