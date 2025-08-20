import { ComponentFixture, TestBed } from '@angular/core/testing';

import { KardexManagement } from './kardex-management';

describe('KardexManagement', () => {
  let component: KardexManagement;
  let fixture: ComponentFixture<KardexManagement>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [KardexManagement]
    })
    .compileComponents();

    fixture = TestBed.createComponent(KardexManagement);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
