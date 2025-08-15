import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AddNewProductModal } from './add-new-product-modal';

describe('AddNewProductModal', () => {
  let component: AddNewProductModal;
  let fixture: ComponentFixture<AddNewProductModal>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AddNewProductModal]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AddNewProductModal);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
