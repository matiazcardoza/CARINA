import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WarrantyControl } from './warranty-control';

describe('WarrantyControl', () => {
  let component: WarrantyControl;
  let fixture: ComponentFixture<WarrantyControl>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [WarrantyControl]
    })
    .compileComponents();

    fixture = TestBed.createComponent(WarrantyControl);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
