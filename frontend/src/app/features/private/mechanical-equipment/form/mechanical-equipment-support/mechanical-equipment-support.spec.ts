import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MechanicalEquipmentSupport } from './mechanical-equipment-support';

describe('MechanicalEquipmentSupport', () => {
  let component: MechanicalEquipmentSupport;
  let fixture: ComponentFixture<MechanicalEquipmentSupport>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MechanicalEquipmentSupport]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MechanicalEquipmentSupport);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
