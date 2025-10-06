import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SeeMovementsDetailsModal } from './see-movements-details-modal';

describe('SeeMovementsDetailsModal', () => {
  let component: SeeMovementsDetailsModal;
  let fixture: ComponentFixture<SeeMovementsDetailsModal>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [SeeMovementsDetailsModal]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SeeMovementsDetailsModal);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
