import { TestBed } from '@angular/core/testing';

import { FamiliaresService } from './familiares.service';

describe('FamiliaresService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: FamiliaresService = TestBed.get(FamiliaresService);
    expect(service).toBeTruthy();
  });
});
