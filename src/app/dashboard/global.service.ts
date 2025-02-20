import { Injectable } from '@angular/core';
import {HttpClient, HttpParams} from '@angular/common/http';
import {Observable} from 'rxjs';
import {Sexo} from '../classes/sexo';
import {environment} from '../../environments/environment';
import {map} from 'rxjs/operators';
import {Categoria} from '../classes/categoria';
import {Competicion} from '../classes/competicion';
import {TipoCategoria} from '../classes/tipo_categoria';
import {TipoPago} from '../classes/tipo_pago';

@Injectable({
  providedIn: 'root'
})
export class GlobalService {
  private API_URL = environment.API_URL;

  constructor(private http: HttpClient) { }

  getSexos(): Observable<Sexo[]> {
    const params = new HttpParams().set('tipo', 'sexo');
    return this.http.get(this.API_URL + '/global.php', {params}).pipe(
      map((res) => res['sexos'])
    );
  }

  getCategorias(): Observable<Categoria[]> {
    const params = new HttpParams().set('tipo', 'categorias');
    return this.http.get(this.API_URL + '/global.php', {params}).pipe(
      map((res) => res['categorias'])
    );
  }

  getCompeticiones(): Observable<Competicion[]> {
    const params = new HttpParams().set('tipo', 'competiciones');
    return this.http.get(this.API_URL + '/global.php', {params}).pipe(
      map((res) => res['competiciones'])
    );
  }

  getTiposCategorias(): Observable<TipoCategoria[]> {
    const params = new HttpParams().set('tipo', 'tipoCategorias');
    return this.http.get(this.API_URL + '/global.php', {params}).pipe(
      map((res) => res['tiposCategoria'])
    );
  }

  getApiConnections(): Observable<boolean[]> {
    const params = new HttpParams().set('tipo', 'apis');
    return this.http.get(this.API_URL + '/apis.php', {params}).pipe(
      map((res) => res['apis'])
    );
  }

  getTiposPago(): Observable<TipoPago[]> {
    const params = new HttpParams().set('tipo', 'tipoPago');
    return this.http.get(this.API_URL + '/global.php', {params}).pipe(
      map((res) => res['tiposPago'])
    );
  }

  integrate(xarxa: string): Observable<string[]> {
    return this.http.put(this.API_URL + '/apis.php', {xarxa, path: location.pathname}).pipe(
      map( (res) => res as string[])
    );
  }

  postPassword(passw: string) {
    return this.http.post(this.API_URL + '/checkValid.php', {password: passw}).pipe(
      map((res) => res['valido'])
    );
  }
}
