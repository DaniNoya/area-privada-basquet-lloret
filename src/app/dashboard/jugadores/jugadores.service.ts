import { Injectable } from '@angular/core';
import {HttpClient, HttpErrorResponse, HttpParams} from '@angular/common/http';
import {Observable, throwError} from 'rxjs';
import {Jugador} from '../../classes/jugador';
import {catchError, map} from 'rxjs/operators';
import {Familiar} from '../../classes/familiar';
import {environment} from '../../../environments/environment';
import {Entrenador} from '../../classes/entrenador';

@Injectable({
  providedIn: 'root'
})
export class JugadoresService {

  private API_URL = environment.API_URL;

  constructor(private http: HttpClient) { }

  getJugadores(metodoVisualizacion: string, sinFamiliaresAsignados: boolean, sinEquiposAsignados: boolean, conceptoSelecciopnado: number = null, estadoJugador: string = null, exclusiones: string = null): Observable<Jugador[]> {
    let params;
    if (exclusiones !== null) {
      params = new HttpParams()
        .set('metodoVisualizacion', metodoVisualizacion)
        .set('sinFamiliaresAsignados', String(sinFamiliaresAsignados))
        .set('sinEquiposAsignados', String(sinEquiposAsignados))
        .set('conceptoSelecciopnado', String(conceptoSelecciopnado))
        .set('estadoJugador', String(estadoJugador))
        .set('exclusiones', exclusiones);
    } else {
      params = new HttpParams()
        .set('metodoVisualizacion', metodoVisualizacion)
        .set('sinFamiliaresAsignados', String(sinFamiliaresAsignados))
        .set('sinEquiposAsignados', String(sinEquiposAsignados))
        .set('conceptoSelecciopnado', String(conceptoSelecciopnado))
        .set('estadoJugador', String(estadoJugador));
    }
    return this.http.get(this.API_URL + '/jugadores.php', {params}).pipe(
      map((res) => res['jugadores'])
    );
  }

  getJugador(idJugador: number){
    const params = new HttpParams().set('jugador', idJugador.toString());
    return this.http.get(this.API_URL + '/jugadores.php', {params}).pipe(
      map((res) => res['jugador'])
    );
  }

  getFamiliares(idJugador: number): Observable<Familiar[]> {
    const params = new HttpParams().set('jugador', idJugador.toString());
    return this.http.get(this.API_URL + '/jugadores.php', {params}).pipe(
      map((res) => res['familiares'])
    );
  }

  getEntrenadores(metodoVisualizacion: string): Observable<Entrenador[]> {
    const params = new HttpParams().set('metodoVisualizacion', metodoVisualizacion);
    return this.http.get(this.API_URL + '/entrenadores.php', {params}).pipe(
      map((res) => res['entrenadores'])
    );
  }

  getListadoJugadores() {
    // const params = new HttpParams().set('paymentType', tipoMovimiento);
    return this.http.get(this.API_URL + '/jugadores_listado.php').pipe(
      map((res) => res)
    );
  }

  update(jugador: Jugador) {
    return this.http.put(this.API_URL + '/jugadores.php', jugador).pipe(
      map((res) => res),
      catchError(this.updateError)
    );
  }

  store(jugador: Jugador) {
    return this.http.post(this.API_URL + '/jugadores.php', jugador).pipe(
      map((res) => res),
      catchError(this.storeError)
    );
  }

  removeFamiliar(idJugador: number, idFamiliar: number) {
    const params = new HttpParams().set('jugador', idJugador.toString()).set('familiar', idFamiliar.toString());
    return this.http.delete(this.API_URL + '/tipos_parentesco.php', {params}).pipe(
      map((res) => res),
      catchError(this.removefamiliarError)
    );
  }

  assign(id: number) {
    return this.http.put(this.API_URL + '/jugadores.php', {idAssign: id}).pipe(
      map((res) => res),
      catchError(this.updateError)
    );
  }

  private removefamiliarError(error: HttpErrorResponse) {
    // return an observable with a user friendly message
    return throwError('Error al eliminar familiar.');
  }

  private updateError(error: HttpErrorResponse) {
    console.log(error);

    // return an observable with a user friendly message
    return throwError('Error al actualizar jugador.');
  }
  private storeError(error: HttpErrorResponse) {
    console.log(error);

    // return an observable with a user friendly message
    return throwError('Error al añadir jugador.');
  }
}
