import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AlertConfirm } from './alert-confirm';

describe('AlertConfirm', () => {
  let component: AlertConfirm;
  let fixture: ComponentFixture<AlertConfirm>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AlertConfirm]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AlertConfirm);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
