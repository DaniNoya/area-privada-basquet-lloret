import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { EditarJugadorDialogComponent } from './editar-jugador-dialog.component';

describe('EditarJugadorDialogComponent', () => {
  let component: EditarJugadorDialogComponent;
  let fixture: ComponentFixture<EditarJugadorDialogComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ EditarJugadorDialogComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(EditarJugadorDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
