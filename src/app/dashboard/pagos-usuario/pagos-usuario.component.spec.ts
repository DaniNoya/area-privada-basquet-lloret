import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { PagosUsuarioComponent } from './pagos-usuario.component';

describe('PagosUsuarioComponent', () => {
  let component: PagosUsuarioComponent;
  let fixture: ComponentFixture<PagosUsuarioComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ PagosUsuarioComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(PagosUsuarioComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
