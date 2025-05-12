import { TestBed } from '@angular/core/testing';

import { TemporadasService } from './temporadas.service';

describe('TemporadasService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: TemporadasService = TestBed.get(TemporadasService);
    expect(service).toBeTruthy();
  });
});
