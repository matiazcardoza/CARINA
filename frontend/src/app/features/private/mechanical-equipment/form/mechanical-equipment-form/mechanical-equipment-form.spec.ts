import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MechanicalEquipmentForm } from './mechanical-equipment-form';

describe('MechanicalEquipmentForm', () => {
  let component: MechanicalEquipmentForm;
  let fixture: ComponentFixture<MechanicalEquipmentForm>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MechanicalEquipmentForm]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MechanicalEquipmentForm);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
