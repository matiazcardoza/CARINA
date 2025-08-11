import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ProjectBasedTraceability } from './project-based-traceability';

describe('ProjectBasedTraceability', () => {
  let component: ProjectBasedTraceability;
  let fixture: ComponentFixture<ProjectBasedTraceability>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ProjectBasedTraceability]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ProjectBasedTraceability);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
