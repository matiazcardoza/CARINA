import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ShowAllObrasModal } from './show-all-obras-modal';

describe('ShowAllObrasModal', () => {
  let component: ShowAllObrasModal;
  let fixture: ComponentFixture<ShowAllObrasModal>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ShowAllObrasModal]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ShowAllObrasModal);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
