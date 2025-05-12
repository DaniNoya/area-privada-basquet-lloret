import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { EditarFamiliarDialogComponent } from './editar-familiar-dialog.component';

describe('EditarFamiliarDialogComponent', () => {
  let component: EditarFamiliarDialogComponent;
  let fixture: ComponentFixture<EditarFamiliarDialogComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ EditarFamiliarDialogComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(EditarFamiliarDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
