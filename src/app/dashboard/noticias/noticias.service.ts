import { Injectable } from '@angular/core';
import {environment} from '../../../environments/environment';
import {HttpClient, HttpErrorResponse, HttpParams} from '@angular/common/http';
import {Noticia} from '../../classes/noticia';
import {catchError, map} from 'rxjs/operators';
import {Observable, throwError} from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class NoticiasService {
  private API_URL = environment.API_URL;

  constructor(private http: HttpClient) { }

  save(noticia: Noticia, facebook: boolean, instagram: boolean, twitter: boolean) {
    return this.http.post(this.API_URL + '/noticias.php', {noticia, facebook, instagram, twitter}).pipe(
      map( (res) => res),
      catchError(this.error)
    );
  }

  edit(noticia: Noticia) {
    return this.http.put(this.API_URL + '/noticias.php', {noticia}).pipe(
      map( (res) => res),
      catchError(this.error)
    );
  }

  getNoticias(tipo: string): Observable<Noticia[]> {
    const params = new HttpParams().set('tipo', tipo);
    return this.http.get(this.API_URL + '/noticias.php', {params}).pipe(
      map( (res) => res['noticias'])
    );
  }

  delete(noticia: Noticia) {
    return this.http.put(this.API_URL + '/noticias.php', {delete: 'true', noticia}).pipe(
      map( (res) => res),
      catchError(this.error)
    );
  }

  enableDisableXarxes(noticia: Noticia, xarxa: string) {
   return this.http.put(this.API_URL + '/noticias.php', {enableDisable: 'true', noticia, xarxa}).pipe(
     map((res) => res),
     catchError(this.errorXarxes)
   );
  }

  private error(error: HttpErrorResponse) {
    console.log(error);

    // return an observable with a user friendly message
    return throwError('Error al guardar noticia/crónica.');
  }

  private errorXarxes(error: HttpErrorResponse) {
    console.log(error);

    // return an observable with a user friendly message
    return throwError('Error al activar/desactivar noticia/crónica en redes.');
  }
}
