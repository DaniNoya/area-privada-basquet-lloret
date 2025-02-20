import {Injectable} from '@angular/core';
import {HttpClient, HttpErrorResponse, HttpParams} from '@angular/common/http';
import {Observable, throwError} from 'rxjs';
import {Perfil} from '../../classes/Perfil';
import {Jugador} from '../../classes/jugador';
import { Socio } from '../../classes/socio';
import {catchError, map} from 'rxjs/operators';
import {environment} from '../../../environments/environment';

@Injectable({
    providedIn: 'root'
})
export class PerfilService {

    private API_URL = environment.API_URL;

    constructor(private http: HttpClient) { }

    getPersona(idUsuario: number): Observable<Perfil> {
        let params;
        params = new HttpParams().set('idUsuario', idUsuario.toString());
        
        return this.http.get(this.API_URL + '/perfil.php', {params}).pipe(
          map((res) => res['persona'])
        );
    }

    getIsSocio(idUsuario: number) {
      let params = new HttpParams().set('idUsuario', idUsuario.toString());
      
      return this.http.get(this.API_URL + '/isSocio.php', {params}).pipe(
        map((res) => res['isSocio'])
      );
    }

    getIsSocioInTemporada(idUsuario: number) {
      let params = new HttpParams().set('idUsuario', idUsuario.toString());
      
      return this.http.get(this.API_URL + '/isSocioInTemporada.php', {params}).pipe(
        map((res) => res['isSocioInTemporada'])
      );
    }

    getSocio(idUsuario: number): Observable<Socio> {
      let params = new HttpParams().set('idUsuario', idUsuario.toString());
      
      return this.http.get(this.API_URL + '/socio.php', {params}).pipe(
        map((res) => res['socio'])
      );
    }

    getEsTutor(idUsuario: number) {
      let params;
      params = new HttpParams().set('idUsuario', idUsuario.toString());
      
      return this.http.get(this.API_URL + '/soyTutor.php', {params}).pipe(
        map((res) => res['soyTutor'])
      );
    }

    getJugadores(idUsuario: number, metodoVisualizacion: string, exclusiones: string = null): Observable<Jugador[]> {
      let params;
      if (exclusiones !== null) {
        params = new HttpParams()
          .set('idUsuario', idUsuario.toString())
          .set('metodoVisualizacion', metodoVisualizacion)
          .set('exclusiones', exclusiones);
      } else {
        params = new HttpParams()
        .set('idUsuario', idUsuario.toString())
        .set('metodoVisualizacion', metodoVisualizacion)
      }
      return this.http.get(this.API_URL + '/familiaresTutor.php', {params}).pipe(
        map((res) => res['familiares'])
      );
    }

    update(perfil: Perfil) {
      return this.http.put(this.API_URL + '/perfil.php', perfil).pipe(
        map((res) => res),
        catchError(this.updatePerfilError)
      );
    }

    assign(id: number) {
        return this.http.put(this.API_URL + '/perfil.php', {idAssign: id}).pipe(
            map((res) => res),
            catchError(this.updatePerfilError)
        );
    }

    updateJugador(jugador: Jugador) {
      return this.http.put(this.API_URL + '/familiaresTutor.php', jugador).pipe(
        map((res) => res),
        catchError(this.updateJugadorError)
      );
    }

    store(idUsuario: number,  tipoParentesco: number, jugador: Jugador) {
      return this.http.post(this.API_URL + '/familiaresTutor.php', {idTutor: idUsuario, TipoParentesco: tipoParentesco, Jugador: jugador}).pipe(
        map((res) => res),
        catchError(this.storeJugadorError)
      );
    }

    assignJugador(id: number) {
      return this.http.put(this.API_URL + '/familiaresTutor.php', {idAssign: id}).pipe(
          map((res) => res),
          catchError(this.updateJugadorError)
      );
    }

    private updatePerfilError(error: HttpErrorResponse) {
        // return an observable with a user friendly message
        return throwError('Error al modificar el perfil.');
    }

    private updateJugadorError(errorJugador: HttpErrorResponse) {
      // return an observable with a user friendly message
      return throwError('Error al modificar el jugador.');
  }

  private storeJugadorError(errorJugador: HttpErrorResponse) {
    // return an observable with a user friendly message
    return throwError('Error al añadir el jugador.');
  }
}
