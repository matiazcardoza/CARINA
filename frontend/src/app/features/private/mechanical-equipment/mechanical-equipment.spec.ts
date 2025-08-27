import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MechanicalEquipment } from './mechanical-equipment';

describe('MechanicalEquipment', () => {
  let component: MechanicalEquipment;
  let fixture: ComponentFixture<MechanicalEquipment>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MechanicalEquipment]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MechanicalEquipment);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
