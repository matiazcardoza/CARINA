import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ValuedKardex } from './valued-kardex';

describe('ValuedKardex', () => {
  let component: ValuedKardex;
  let fixture: ComponentFixture<ValuedKardex>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ValuedKardex]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ValuedKardex);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
