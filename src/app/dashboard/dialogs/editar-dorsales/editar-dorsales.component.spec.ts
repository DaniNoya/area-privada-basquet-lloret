import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { EditarDorsalesComponent } from './editar-dorsales.component';

describe('EditarDorsalesComponent', () => {
  let component: EditarDorsalesComponent;
  let fixture: ComponentFixture<EditarDorsalesComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ EditarDorsalesComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(EditarDorsalesComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
