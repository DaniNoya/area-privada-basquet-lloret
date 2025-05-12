import { TestBed } from '@angular/core/testing';

import { SociosService } from './socios.service';

describe('SociosService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: SociosService = TestBed.get(SociosService);
    expect(service).toBeTruthy();
  });
});
