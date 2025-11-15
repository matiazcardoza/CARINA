import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ReportsId } from './reports-id';

describe('ReportsId', () => {
  let component: ReportsId;
  let fixture: ComponentFixture<ReportsId>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ReportsId]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ReportsId);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
