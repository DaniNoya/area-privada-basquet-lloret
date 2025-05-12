import { TestBed } from '@angular/core/testing';

import { ModulosService } from './modulos.service';

describe('ModulosService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: ModulosService = TestBed.get(ModulosService);
    expect(service).toBeTruthy();
  });
});
