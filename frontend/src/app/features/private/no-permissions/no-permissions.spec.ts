import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NoPermissions } from './no-permissions';

describe('NoPermissions', () => {
  let component: NoPermissions;
  let fixture: ComponentFixture<NoPermissions>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [NoPermissions]
    })
    .compileComponents();

    fixture = TestBed.createComponent(NoPermissions);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
