import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TestResquests } from './test-resquests';

describe('TestResquests', () => {
  let component: TestResquests;
  let fixture: ComponentFixture<TestResquests>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TestResquests]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TestResquests);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
