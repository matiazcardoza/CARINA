import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MySecondFeature } from './my-second-feature';

describe('MySecondFeature', () => {
  let component: MySecondFeature;
  let fixture: ComponentFixture<MySecondFeature>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MySecondFeature]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MySecondFeature);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
