import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FuelVouchers } from './fuel-vouchers';

describe('FuelVouchers', () => {
  let component: FuelVouchers;
  let fixture: ComponentFixture<FuelVouchers>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [FuelVouchers]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FuelVouchers);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
