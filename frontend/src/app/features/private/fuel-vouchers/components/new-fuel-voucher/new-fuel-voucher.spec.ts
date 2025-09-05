import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NewFuelVoucher } from './new-fuel-voucher';

describe('NewFuelVoucher', () => {
  let component: NewFuelVoucher;
  let fixture: ComponentFixture<NewFuelVoucher>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [NewFuelVoucher]
    })
    .compileComponents();

    fixture = TestBed.createComponent(NewFuelVoucher);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
