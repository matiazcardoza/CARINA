import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ComponentTesting } from './component-testing';

describe('ComponentTesting', () => {
  let component: ComponentTesting;
  let fixture: ComponentFixture<ComponentTesting>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ComponentTesting]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ComponentTesting);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
