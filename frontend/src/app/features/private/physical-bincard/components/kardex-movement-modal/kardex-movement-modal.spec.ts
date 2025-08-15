import { ComponentFixture, TestBed } from '@angular/core/testing';

import { KardexMovementModal } from './kardex-movement-modal';

describe('KardexMovementModal', () => {
  let component: KardexMovementModal;
  let fixture: ComponentFixture<KardexMovementModal>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [KardexMovementModal]
    })
    .compileComponents();

    fixture = TestBed.createComponent(KardexMovementModal);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
