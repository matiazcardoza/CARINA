import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MechanicalEquipmentWork } from './mechanical-equipment-work';

describe('MechanicalEquipmentWork', () => {
  let component: MechanicalEquipmentWork;
  let fixture: ComponentFixture<MechanicalEquipmentWork>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MechanicalEquipmentWork]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MechanicalEquipmentWork);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
