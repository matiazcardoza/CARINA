import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Conformity } from './conformity';

describe('Conformity', () => {
  let component: Conformity;
  let fixture: ComponentFixture<Conformity>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Conformity]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Conformity);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
