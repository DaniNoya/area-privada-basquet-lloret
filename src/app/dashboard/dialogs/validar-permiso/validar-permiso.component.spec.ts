import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ValidarPermisoComponent } from './validar-permiso.component';

describe('ValidarPermisoComponent', () => {
  let component: ValidarPermisoComponent;
  let fixture: ComponentFixture<ValidarPermisoComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ValidarPermisoComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ValidarPermisoComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
