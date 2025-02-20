import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { DirectivosComponent } from './directivos.component';

describe('DirectivosComponent', () => {
  let component: DirectivosComponent;
  let fixture: ComponentFixture<DirectivosComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ DirectivosComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(DirectivosComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
