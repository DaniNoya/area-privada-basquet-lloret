import { Injectable } from '@angular/core';
import {environment} from '../../../environments/environment';
import {HttpClient, HttpErrorResponse, HttpParams} from '@angular/common/http';
import {Observable, pipe, throwError} from 'rxjs';
import {Temporada} from '../../classes/temporada';
import {Importes} from '../../classes/Importes';
import {catchError, map} from 'rxjs/operators';
import {Equipo} from '../../classes/equipo';
import {Jugador} from '../../classes/jugador';
import {Entrenador} from '../../classes/entrenador';

@Injectable({
  providedIn: 'root'
})
export class TemporadasService {

  private API_URL = environment.API_URL;

  constructor(private http: HttpClient) { }

  getTemporadas(): Observable<Temporada[]> {
    return this.http.get(this.API_URL + '/temporadas.php').pipe(
      map( (res) => res['temporadas'])
    );
  }

  getEquipos(): Observable<Equipo[]> {
    const params = new HttpParams().set('equipos', 'true');
    return this.http.get(this.API_URL + '/temporadas.php', {params}).pipe(
      map( (res) => res['equipos'])
    );
  }

  getListadoEquipos() {
    return this.http.get(this.API_URL + '/equipos_listado.php').pipe(
      map( (res) => res)
    );
  }
  getListadoPartidos() {
    return this.http.get(this.API_URL + '/asistencia_partidos.php').pipe(
      map( (res) => res)
    );
  }

  getImportes(metodoVisualizacion: string): Observable<Importes[]> {
    let params;

    params = new HttpParams().set('metodoVisualizacion', metodoVisualizacion);

    return this.http.get(this.API_URL + '/importes.php', {params}).pipe(
      map( (res) => res['importes'])
    );
  }

  getJugadores(equipo: Equipo, filtro: string = '') {
    const params = new HttpParams().set('jugadores', 'true').set('equipo', String(equipo.id)).set('filtro', filtro);
    return this.http.get(this.API_URL + '/temporadas.php', {params}).pipe(
      map( (res) => res)
    );
  }

  getEntrenadores(equipo: Equipo, filtro: string = '') {
    const params = new HttpParams().set('entrenadores', 'true').set('equipo', String(equipo.id)).set('filtro', filtro);
    return this.http.get(this.API_URL + '/temporadas.php', {params}).pipe(
      map( (res) => res)
    );
  }

  getDelegados(equipo: Equipo, filtro: string = '') {
    const params = new HttpParams().set('delegados', 'true').set('equipo', String(equipo.id)).set('filtro', filtro);
    return this.http.get(this.API_URL + '/temporadas.php', {params}).pipe(
      map( (res) => res)
    );
  }

  getTiposDescuentos(): Observable<Importes[]> {
    return this.http.get(this.API_URL + '/descuentos.php').pipe(
      map( (res) => res['tiposDescuentos'])
    );
  }



  store(temporada: Temporada) {

    return this.http.post(this.API_URL + '/temporadas.php', temporada).pipe(
      map((res) => res),
      catchError(this.storeError)
    );
  }

  storeEquipo(equipo: Equipo) {
    return this.http.post(this.API_URL + '/equipos.php', equipo).pipe(
      map((res) => res),
      catchError(this.storeEquipoError)
    );
  }

  updateEquipo(equipo: Equipo) {
    return this.http.put(this.API_URL + '/equipos.php', equipo).pipe(
      map((res) => res),
      catchError(this.updateEquipoError)
    );
  }

  switchActivoWeb(equipo: Equipo) {
    const params = new HttpParams().set('switchActivoWeb', 'true')
      .set('equipo', JSON.stringify(equipo));
    return this.http.put(this.API_URL + '/equipos.php', {params}).pipe(
      map((res) => res),
      catchError(this.updateEquipoError)
    );
  }

  eliminarEquipo(equipo: Equipo) {
    const params = new HttpParams().set('eliminarEquipo', 'true')
      .set('equipo', JSON.stringify(equipo));
    return this.http.put(this.API_URL + '/equipos.php', {params}).pipe(
      map((res) => res),
      catchError(this.eliminarEquipoError)
    );
  }

  updateFoto(jugador: Jugador, equipo: Equipo) {
    const params = new HttpParams().set('editarFoto', 'true')
      .set('jugador', JSON.stringify(jugador))
      .set('equipo', JSON.stringify(equipo));
    return this.http.put(this.API_URL + '/equipos.php', {params}).pipe(
      map((res) => res),
      catchError(this.updateFotoError)
    );
  }

  updateDorsal(jugador: Jugador, equipo: Equipo) {
    const params = new HttpParams().set('editarDorsal', 'true')
      .set('jugador', JSON.stringify(jugador))
      .set('equipo', JSON.stringify(equipo));
    return this.http.put(this.API_URL + '/equipos.php', {params}).pipe(
      map((res) => res),
      catchError(this.updateDorsalError)
    );
  }

  anyadirAEquipo(jugadores: Jugador[], equipo: Equipo) {
    const params = new HttpParams().set('anyadirJugadores', 'true')
      .set('jugadores', JSON.stringify(jugadores))
      .set('equipo', JSON.stringify(equipo));
    return this.http.put(this.API_URL + '/equipos.php', {params}).pipe(
      map((res) => res),
      catchError(this.anyadirEliminarJugadores)
    );
  }

  eliminarDeEquipo(jugadores: Jugador[], equipo: Equipo) {
    const params = new HttpParams().set('eliminarJugadores', 'true')
      .set('jugadores', JSON.stringify(jugadores))
      .set('equipo', JSON.stringify(equipo));
    return this.http.put(this.API_URL + '/equipos.php', {params}).pipe(
      map((res) => res),
      catchError(this.anyadirEliminarJugadores)
    );
  }

  anyadirEntrenador(entrenadores: Entrenador[], idTipoEntrenador: number, equipo: Equipo) {
    const params = new HttpParams().set('anyadirEntrenador', 'true')
      .set('entrenadores', JSON.stringify(entrenadores))
      .set('idTipoEntrenador', String(idTipoEntrenador))
      .set('equipo', JSON.stringify(equipo));
    return this.http.put(this.API_URL + '/equipos.php', {params}).pipe(
      map((res) => res),
      catchError(this.anyadirEliminarEntrenadores)
    );
  }

  eliminarEntrenador(entrenadores: Entrenador[], equipo: Equipo) {
    const params = new HttpParams().set('eliminarEntrenador', 'true')
      .set('entrenadores', JSON.stringify(entrenadores))
      .set('equipo', JSON.stringify(equipo));
    return this.http.put(this.API_URL + '/equipos.php', {params}).pipe(
      map((res) => res),
      catchError(this.anyadirEliminarEntrenadores)
    );
  }

  anyadirDelegado(delegados: Array<number>, equipo: Equipo) {
    const params = new HttpParams().set('anyadirDelegado', 'true')
      .set('delegados', JSON.stringify(delegados))
      .set('equipo', JSON.stringify(equipo));
    return this.http.put(this.API_URL + '/equipos.php', {params}).pipe(
      map((res) => res),
      catchError(this.anyadirEliminarFamiliares)
    );
  }

  eliminarDelegado(delegados: Array<number>, equipo: Equipo) {
    const params = new HttpParams().set('eliminarDelegado', 'true')
      .set('delegados', JSON.stringify(delegados))
      .set('equipo', JSON.stringify(equipo));
    return this.http.put(this.API_URL + '/equipos.php', {params}).pipe(
      map((res) => res),
      catchError(this.anyadirEliminarFamiliares)
    );
  }

  private storeError(error: HttpErrorResponse) {
    console.log(error);

    // return an observable with a user friendly message
    return throwError('Error al crear temporada.');
  }

  private storeEquipoError(error: HttpErrorResponse) {
    console.log(error);

    // return an observable with a user friendly message
    return throwError('Error al crear equipo.');
  }

  updateEquipoError(error: HttpErrorResponse) {
    console.log(error);

    // return an observable with a user friendly message
    return throwError('Error al modificar equipo.');
  }

  eliminarEquipoError(error: HttpErrorResponse) {
    console.log(error);

    // return an observable with a user friendly message
    return throwError('Error al eliminar equipo');
  }

  updateFotoError(error: HttpErrorResponse) {
    console.log(error);

    // return an observable with a user friendly message
    return throwError('Error al guardar la imagen.');
  }

  updateDorsalError(error: HttpErrorResponse) {
    console.log(error);

    // return an observable with a user friendly message
    return throwError('Error al guardar el dorsal, por favor compruebe que no esté ya asignado.');
  }

  anyadirEliminarJugadores(error: HttpErrorResponse) {
  console.log(error);

  // return an observable with a user friendly message
  return throwError('Error al modificar jugadores asignados.');
  }

  anyadirEliminarEntrenadores(error: HttpErrorResponse) {
    console.log(error);

    // return an observable with a user friendly message
    return throwError('Error al modificar entrenadores asignados.');
  }

  anyadirEliminarFamiliares(error: HttpErrorResponse) {
    console.log(error);

    // return an observable with a user friendly message
    return throwError('Error al modificar familiares asignados.');
  }
}
