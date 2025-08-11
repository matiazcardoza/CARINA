import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MyThirdFeature } from './my-third-feature';

describe('MyThirdFeature', () => {
  let component: MyThirdFeature;
  let fixture: ComponentFixture<MyThirdFeature>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MyThirdFeature]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MyThirdFeature);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
