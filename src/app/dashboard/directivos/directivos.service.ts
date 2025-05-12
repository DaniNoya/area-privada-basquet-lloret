import { Injectable } from '@angular/core';
import {HttpClient, HttpErrorResponse, HttpParams} from '@angular/common/http';
import {Observable, throwError} from 'rxjs';
import {Directivo} from '../../classes/directivo';
import {catchError, map} from 'rxjs/operators';
import {Cargo} from '../../classes/cargo';
import {environment} from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class DirectivosService {

  private API_URL = environment.API_URL;

  constructor(private http: HttpClient) { }

  getDirectivos(metodoVisualizacion: string, exclusiones: string = null): Observable<Directivo[]> {
    let params;
    if (exclusiones !== null) {
      params = new HttpParams()
        .set('metodoVisualizacion', metodoVisualizacion)
        .set('exclusiones', exclusiones);
    } else {
      params = new HttpParams()
        .set('metodoVisualizacion', metodoVisualizacion)
    }
    return this.http.get(this.API_URL + '/directivos.php', {params}).pipe(
      map((res) => res['directivos'])
    );
  }
  getCargos(): Observable<Cargo[]> {
    return this.http.get(this.API_URL + '/cargos.php').pipe(
      map((res) => res['cargos'])
    );
  }

  update(directivo: Directivo) {
    return this.http.put(this.API_URL + '/directivos.php', directivo).pipe(
      map((res) => res),
      catchError(this.updateDirectivoError)
    );
  }

  store(directivo: Directivo) {
    return this.http.post(this.API_URL + '/directivos.php', directivo).pipe(
      map((res) => res),
      catchError(this.storeDirectivoError)
    );
  }

  assign(id: number) {
    return this.http.put(this.API_URL + '/directivos.php', {idAssign: id}).pipe(
      map((res) => res),
      catchError(this.updateDirectivoError)
    );
  }

  private updateDirectivoError(error: HttpErrorResponse) {
    // return an observable with a user friendly message
    return throwError('Error al modificar directivo.');
  }

  private storeDirectivoError(error: HttpErrorResponse) {
    // return an observable with a user friendly message
    return throwError('Error al crear directivo.');
  }
}
