import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FuelControl } from './fuel-control';

describe('FuelControl', () => {
  let component: FuelControl;
  let fixture: ComponentFixture<FuelControl>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [FuelControl]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FuelControl);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
