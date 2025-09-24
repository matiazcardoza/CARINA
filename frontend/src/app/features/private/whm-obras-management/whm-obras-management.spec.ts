import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WhmObrasManagement } from './whm-obras-management';

describe('WhmObrasManagement', () => {
  let component: WhmObrasManagement;
  let fixture: ComponentFixture<WhmObrasManagement>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [WhmObrasManagement]
    })
    .compileComponents();

    fixture = TestBed.createComponent(WhmObrasManagement);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
