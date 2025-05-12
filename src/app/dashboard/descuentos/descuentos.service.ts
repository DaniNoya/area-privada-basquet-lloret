import {Injectable} from '@angular/core';
import {HttpClient, HttpErrorResponse, HttpParams} from '@angular/common/http';
import {Observable, throwError} from 'rxjs';
import {Descuento} from '../../classes/Descuento';
import {TipoPago} from '../../classes/tipo_pago';
import {Importes} from '../../classes/importes';
import {catchError, map} from 'rxjs/operators';
import {environment} from '../../../environments/environment';

@Injectable({
    providedIn: 'root'
})
export class DescuentosService {

    private API_URL = environment.API_URL;

    constructor(private http: HttpClient) { }

    getDescuentos(metodoVisualizacion: string, exclusiones: string = null): Observable<Descuento[]> {
        let params;
        if (exclusiones !== null) {
          params = new HttpParams()
            .set('metodoVisualizacion', metodoVisualizacion)
            .set('exclusiones', exclusiones);
        } else {
          params = new HttpParams()
            .set('metodoVisualizacion', metodoVisualizacion)
        }
        return this.http.get(this.API_URL + '/descuentos.php', {params}).pipe(
          map((res) => res['descuentos'])
        );
    }

    getConceptoDescuentos(): Observable<TipoPago[]> {
      return this.http.get(this.API_URL + '/tipo_pago.php').pipe(
        map( (res) => res['conceptoDescuentos'])
      );
    }

    getTiposDescuentos(): Observable<Importes[]> {
      return this.http.get(this.API_URL + '/descuentos.php').pipe(
        map( (res) => res['tiposDescuentos'])
      );
    }

    update(descuento: Descuento) {
      return this.http.put(this.API_URL + '/descuentos.php', descuento).pipe(
        map((res) => res),
        catchError(this.updateDescuentoError)
      );
    }

    store(descuento: Descuento) {
      return this.http.post(this.API_URL + '/descuentos.php', descuento).pipe(
        map((res) => res),
        catchError(this.storeDescuentoError)
      );
    }

    assign(id: number) {
        return this.http.put(this.API_URL + '/descuentos.php', {idAssign: id}).pipe(
            map((res) => res),
            catchError(this.updateDescuentoError)
        );
    }

    private updateDescuentoError(error: HttpErrorResponse) {
        // return an observable with a user friendly message
        return throwError('Error al modificar el descuento.');
    }

    private storeDescuentoError(error: HttpErrorResponse) {
        // return an observable with a user friendly message
        return throwError('Error al crear el descuento.');
    }
}
