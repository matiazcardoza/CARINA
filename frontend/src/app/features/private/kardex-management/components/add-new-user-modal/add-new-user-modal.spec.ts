import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AddNewUserModal } from './add-new-user-modal';

describe('AddNewUserModal', () => {
  let component: AddNewUserModal;
  let fixture: ComponentFixture<AddNewUserModal>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AddNewUserModal]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AddNewUserModal);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
