import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TablePrimeng } from './table-primeng';

describe('TablePrimeng', () => {
  let component: TablePrimeng;
  let fixture: ComponentFixture<TablePrimeng>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TablePrimeng]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TablePrimeng);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
