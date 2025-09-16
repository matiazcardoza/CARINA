import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WhmKardexManagement } from './whm-kardex-management';

describe('WhmKardexManagement', () => {
  let component: WhmKardexManagement;
  let fixture: ComponentFixture<WhmKardexManagement>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [WhmKardexManagement]
    })
    .compileComponents();

    fixture = TestBed.createComponent(WhmKardexManagement);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
