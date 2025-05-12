import { Injectable } from '@angular/core';
import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError, map } from 'rxjs/operators';
import { environment } from '../../../environments/environment';
import { Partido } from 'src/app/classes/partido';
import { Equipo } from '../../classes/equipo';

@Injectable({
    providedIn: 'root'
})
export class PartidosService {

  private API_URL = environment.API_URL;

  constructor(private http: HttpClient) { }

  getPartidos(): Observable<object[]> {
    return this.http.get(this.API_URL + '/partidosList.php').pipe(
      map((res) => res['partidos'])
    );
  }

  getEquipos(): Observable<Equipo[]> {
    return this.http.get(this.API_URL + '/partidosList.php').pipe(
      map((res) => res['equipos'])
    );
  }

  update(partido: Partido) {
    return this.http.put(this.API_URL + '/partidosList.php', partido).pipe(
      map((res) => res),
      catchError(this.updatePartidoError)
    );
  }

  store(partido: Partido) {
    return this.http.post(this.API_URL + '/partidosList.php', partido).pipe(
      map((res) => res),
      catchError(this.storePartidoError)
    );
  }

  private updatePartidoError(error: HttpErrorResponse) {
    // return an observable with a user friendly message
    return throwError('Error al modificar el partido.');
  }

  private storePartidoError(error: HttpErrorResponse) {
    // return an observable with a user friendly message
    return throwError('Error al crear el partido.');
  }

}