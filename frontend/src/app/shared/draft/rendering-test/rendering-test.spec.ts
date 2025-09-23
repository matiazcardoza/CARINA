import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RenderingTest } from './rendering-test';

describe('RenderingTest', () => {
  let component: RenderingTest;
  let fixture: ComponentFixture<RenderingTest>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [RenderingTest]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RenderingTest);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
