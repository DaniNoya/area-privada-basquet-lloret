import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { FamiliaresDialogComponent } from './familiares-dialog.component';

describe('FamiliaresDialogComponent', () => {
  let component: FamiliaresDialogComponent;
  let fixture: ComponentFixture<FamiliaresDialogComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ FamiliaresDialogComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(FamiliaresDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
