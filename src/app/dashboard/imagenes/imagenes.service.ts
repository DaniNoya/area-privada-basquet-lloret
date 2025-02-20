import { Injectable } from '@angular/core';
import {environment} from '../../../environments/environment';
import {HttpClient, HttpErrorResponse, HttpParams} from '@angular/common/http';
import {Equipo} from '../../classes/equipo';
import {catchError, map} from 'rxjs/operators';
import {Observable, throwError} from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class ImagenesService {

  private API_URL = environment.API_URL;

  constructor(private http: HttpClient) { }

  subirAEquipo(equipo: Equipo, fotos: Array<string>) {
    return this.http.post(this.API_URL + '/imagenes.php', {equipo, fotos}).pipe(
      map((res) => res),
      catchError(this.imagenesError)
    );
  }

  updateEquipo(equipo: Equipo, fotos: Array<string>) {
    return this.http.put(this.API_URL + '/imagenes.php', {equipo, fotos}).pipe(
      map((res) => res),
      catchError(this.imagenesError)
    );
  }

  subirAClub(fotos: Array<string>) {
    return this.http.post(this.API_URL + '/imagenes.php', {fotos}).pipe(
      map((res) => res),
      catchError(this.imagenesError)
    );
  }

  updateClub(fotos: Array<string>) {
    return this.http.put(this.API_URL + '/imagenes.php', {fotos}).pipe(
      map((res) => res),
      catchError(this.imagenesError)
    );
  }

  getImatgesClub(): Observable<Array<string>> {
    const params = new HttpParams().set('club', 'true');
    return this.http.get(this.API_URL + '/imagenes.php', {params}).pipe(
      map( (res) => res['imagenes'])
    );
  }

  getImatgesEquip(equipo: Equipo): Observable<Array<string>> {
    const params = new HttpParams().set('equipo', String(equipo.id));
    return this.http.get(this.API_URL + '/imagenes.php', {params}).pipe(
      map( (res) => res['imagenes'])
    );
  }

  private imagenesError(error: HttpErrorResponse) {
    console.log(error);

    // return an observable with a user friendly message
    return throwError('Error al subir imagenes.');
  }
}
