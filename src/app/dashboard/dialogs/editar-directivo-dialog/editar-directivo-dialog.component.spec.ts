import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { EditarDirectivoDialogComponent } from './editar-directivo-dialog.component';

describe('EditarDirectivoDialogComponent', () => {
  let component: EditarDirectivoDialogComponent;
  let fixture: ComponentFixture<EditarDirectivoDialogComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ EditarDirectivoDialogComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(EditarDirectivoDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
