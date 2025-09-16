import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DailyWorkSignature } from './daily-work-signature';

describe('DailyWorkSignature', () => {
  let component: DailyWorkSignature;
  let fixture: ComponentFixture<DailyWorkSignature>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DailyWorkSignature]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DailyWorkSignature);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
