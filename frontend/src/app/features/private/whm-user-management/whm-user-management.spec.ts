import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WhmUserManagement } from './whm-user-management';

describe('WhmUserManagement', () => {
  let component: WhmUserManagement;
  let fixture: ComponentFixture<WhmUserManagement>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [WhmUserManagement]
    })
    .compileComponents();

    fixture = TestBed.createComponent(WhmUserManagement);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
