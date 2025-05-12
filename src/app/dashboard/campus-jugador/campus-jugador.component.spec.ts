import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { CampusJugadorComponent } from './campus-jugador.component';

describe('CampusJugadorComponent', () => {
  let component: CampusJugadorComponent;
  let fixture: ComponentFixture<CampusJugadorComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ CampusJugadorComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(CampusJugadorComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});