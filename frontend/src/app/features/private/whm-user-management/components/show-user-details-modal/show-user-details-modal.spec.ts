import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ShowUserDetailsModal } from './show-user-details-modal';

describe('ShowUserDetailsModal', () => {
  let component: ShowUserDetailsModal;
  let fixture: ComponentFixture<ShowUserDetailsModal>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ShowUserDetailsModal]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ShowUserDetailsModal);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
