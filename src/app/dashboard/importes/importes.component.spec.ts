import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ImportesComponent } from './importes.component';

describe('ImportesComponent', () => {
  let component: ImportesComponent;
  let fixture: ComponentFixture<ImportesComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ImportesComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ImportesComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
