import { Injectable } from '@angular/core';
import {HttpClient, HttpErrorResponse, HttpParams} from '@angular/common/http';
import {Observable, throwError} from 'rxjs';
import {Usuario} from '../classes/usuario';
import {Modulo} from '../classes/modulo';
import {catchError, map} from 'rxjs/operators';
import {environment} from '../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class ModulosService {

  private API_URL = environment.API_URL;
  constructor(private http: HttpClient) { }

  getModulos(usuario: number): Observable<Modulo[]> {
    //return this.http.post(this.API_URL + '/modulos.php', usuario).pipe(map((res) => res)),catchError(this.modulosError);
    const params = new HttpParams().set('idUsuario', usuario.toString());
    return this.http.get(this.API_URL + '/modulos.php', {params}).pipe(
      map((res) => res['modulos'])
    );
  }

  private modulosError(error: HttpErrorResponse) {
    console.log(error);

    // return an observable with a user friendly message
    return throwError('Error al consultar los modulos.');
  }
}
