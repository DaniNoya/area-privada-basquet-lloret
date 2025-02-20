import { TestBed } from '@angular/core/testing';

import { DirectivosService } from './directivos.service';

describe('DirectivosService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: DirectivosService = TestBed.get(DirectivosService);
    expect(service).toBeTruthy();
  });
});
