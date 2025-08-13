import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PhysicalBincard } from './physical-bincard';

describe('PhysicalBincard', () => {
  let component: PhysicalBincard;
  let fixture: ComponentFixture<PhysicalBincard>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [PhysicalBincard]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PhysicalBincard);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  
});
