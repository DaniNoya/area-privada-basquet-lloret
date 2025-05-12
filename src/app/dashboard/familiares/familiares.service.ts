import { Injectable } from '@angular/core';
import {HttpClient, HttpErrorResponse, HttpParams} from '@angular/common/http';
import {Observable, throwError} from 'rxjs';
import {TiposParentesco} from '../../classes/tipos_parentesco';
import {catchError, map} from 'rxjs/operators';
import {Familiar} from '../../classes/familiar';
import {environment} from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class FamiliaresService {

  private API_URL = environment.API_URL;

  constructor(public http: HttpClient) { }

  getAll(metodoVisualizacion: string, sinJugadoresAsignados: boolean, exclusiones: string = null): Observable<Familiar[]> {
    let params;
    if (exclusiones !== null) {
      params = new HttpParams()
        .set('metodoVisualizacion', metodoVisualizacion)
        .set('sinJugadoresAsignados', String(sinJugadoresAsignados))
        .set('exclusiones', exclusiones);
    } else {
      params = new HttpParams()
        .set('metodoVisualizacion', metodoVisualizacion)
        .set('sinJugadoresAsignados', String(sinJugadoresAsignados));
    }
    return this.http.get(this.API_URL + '/familiares.php', {params}).pipe(
      map((res) => res['familiares'])
    );
  }
  update(familiar: Familiar) {
    return this.http.put(this.API_URL + '/familiares.php', familiar).pipe(
      map((res) => res),
      catchError(this.updatefamiliarError)
    );
  }

  store(familiar: Familiar) {
    return this.http.post(this.API_URL + '/familiares.php', familiar).pipe(
      map((res) => res),
      catchError(this.storefamiliarError)
    );
  }

  getTipos(): Observable<TiposParentesco[]> {
    return this.http.get(this.API_URL + '/tipos_parentesco.php').pipe(
      map((res) => res['tipos_parentesco'])
    );
  }

  // Enviem l'id de qui serà familiar, l'id de qui serà el jugador amb el familiar assignat i el tipus de parentesco
  setFamiliar(idFamiliar: number, idJugador: number, tipoParentesco: number) {
    return this.http.post(this.API_URL + '/tipos_parentesco.php',
      JSON.stringify({idFamiliar, idJugador, tipoParentesco})).pipe(
      map((res) => res),
      catchError(this.familiarError)
    );
  }

  assign(id: number) {
    return this.http.put(this.API_URL + '/familiares.php', {idAssign: id}).pipe(
      map((res) => res),
      catchError(this.updatefamiliarError)
    );
  }

  private updatefamiliarError(error: HttpErrorResponse) {
    // return an observable with a user friendly message
    return throwError('Error al modificar familiar.');
  }

  private storefamiliarError(error: HttpErrorResponse) {
    // return an observable with a user friendly message
    return throwError('Error al crear familiar.');
  }

  private familiarError(error: HttpErrorResponse) {
    // return an observable with a user friendly message
    return throwError('No se puede añadir el familiar.');
  }
}
