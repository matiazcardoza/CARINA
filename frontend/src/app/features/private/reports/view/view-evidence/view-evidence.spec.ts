import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ViewEvidence } from './view-evidence';

describe('ViewEvidence', () => {
  let component: ViewEvidence;
  let fixture: ComponentFixture<ViewEvidence>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ViewEvidence]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ViewEvidence);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
