import { ComponentFixture, TestBed } from '@angular/core/testing';

import { UserRolesForm } from './user-roles-form';

describe('UserRolesForm', () => {
  let component: UserRolesForm;
  let fixture: ComponentFixture<UserRolesForm>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [UserRolesForm]
    })
    .compileComponents();

    fixture = TestBed.createComponent(UserRolesForm);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
