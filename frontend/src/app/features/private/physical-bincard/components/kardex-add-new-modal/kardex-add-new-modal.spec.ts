import { ComponentFixture, TestBed } from '@angular/core/testing';

import { KardexAddNewModal } from './kardex-add-new-modal';

describe('KardexAddNewModal', () => {
  let component: KardexAddNewModal;
  let fixture: ComponentFixture<KardexAddNewModal>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [KardexAddNewModal]
    })
    .compileComponents();

    fixture = TestBed.createComponent(KardexAddNewModal);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
