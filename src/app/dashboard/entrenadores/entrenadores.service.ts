import { Injectable } from '@angular/core';
import {HttpClient, HttpErrorResponse, HttpParams} from '@angular/common/http';
import {Observable, throwError} from 'rxjs';
import {catchError, map} from 'rxjs/operators';
import {Entrenador} from '../../classes/entrenador';
import {NivelFormacion} from '../../classes/nivel_formacion';
import {environment} from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class EntrenadoresService {

  private API_URL = environment.API_URL;

  constructor(private http: HttpClient) { }

  getEntrenadores(metodoVisualizacion: string, exclusiones: string = null): Observable<Entrenador[]> {
    let params;
    if (exclusiones !== null) {
      params = new HttpParams()
        .set('metodoVisualizacion', metodoVisualizacion)
        .set('exclusiones', exclusiones);
    } else {
      params = new HttpParams()
        .set('metodoVisualizacion', metodoVisualizacion)
    }
    return this.http.get(this.API_URL + '/entrenadores.php', {params}).pipe(
      map((res) => res['entrenadores'])
    );
  }

  getNiveles(): Observable<NivelFormacion[]> {
    return this.http.get(this.API_URL + '/niveles_formacion.php').pipe(
      map((res) => res['niveles_formacion'])
    );
  }

  update(entrenador: Entrenador) {
    return this.http.put(this.API_URL + '/entrenadores.php', entrenador).pipe(
      map((res) => res),
      catchError(this.updateEntrenadorError)
    );
  }

  store(entrenador: Entrenador) {
    return this.http.post(this.API_URL + '/entrenadores.php', entrenador).pipe(
      map((res) => res),
      catchError(this.storeEntrenadorError)
    );
  }

  assign(id: number) {
    return this.http.put(this.API_URL + '/entrenadores.php', {idAssign: id}).pipe(
      map((res) => res),
      catchError(this.updateEntrenadorError)
    );
  }

  private updateEntrenadorError(error: HttpErrorResponse) {
    // return an observable with a user friendly message
    return throwError('Error al modificar entrenador.');
  }

  private storeEntrenadorError(error: HttpErrorResponse) {
    // return an observable with a user friendly message
    return throwError('Error al crear entrenador.');
  }
}
