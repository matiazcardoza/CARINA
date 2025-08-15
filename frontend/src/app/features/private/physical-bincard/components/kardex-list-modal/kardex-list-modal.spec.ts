import { ComponentFixture, TestBed } from '@angular/core/testing';

import { KardexListModal } from './kardex-list-modal';

describe('KardexListModal', () => {
  let component: KardexListModal;
  let fixture: ComponentFixture<KardexListModal>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [KardexListModal]
    })
    .compileComponents();

    fixture = TestBed.createComponent(KardexListModal);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
