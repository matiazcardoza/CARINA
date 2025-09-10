import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RolesPermissions } from './roles-permissions';

describe('RolesPermissions', () => {
  let component: RolesPermissions;
  let fixture: ComponentFixture<RolesPermissions>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [RolesPermissions]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RolesPermissions);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
