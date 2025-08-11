import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ReportsAndDashboards } from './reports-and-dashboards';

describe('ReportsAndDashboards', () => {
  let component: ReportsAndDashboards;
  let fixture: ComponentFixture<ReportsAndDashboards>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ReportsAndDashboards]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ReportsAndDashboards);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
