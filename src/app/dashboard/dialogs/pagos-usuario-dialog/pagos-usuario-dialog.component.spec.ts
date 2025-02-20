import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { PagosUsuarioDialogComponent } from './pagos-usuario-dialog.component';

describe('PagosUsuarioDialogComponent', () => {
  let component: PagosUsuarioDialogComponent;
  let fixture: ComponentFixture<PagosUsuarioDialogComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ PagosUsuarioDialogComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(PagosUsuarioDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
