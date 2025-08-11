import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MyFirtsFeature } from './my-firts-feature';

describe('MyFirtsFeature', () => {
  let component: MyFirtsFeature;
  let fixture: ComponentFixture<MyFirtsFeature>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MyFirtsFeature]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MyFirtsFeature);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
