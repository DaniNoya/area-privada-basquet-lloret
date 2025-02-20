import {Injectable} from '@angular/core';
import {HttpClient, HttpErrorResponse, HttpParams} from '@angular/common/http';
import {Observable, throwError} from 'rxjs';
import {Pago} from '../../classes/Pago';
import {catchError, map} from 'rxjs/operators';
import {environment} from '../../../environments/environment';
import {Jugador} from "../../classes/jugador";
import {PagoModulo} from "./pagos.component";

@Injectable({
    providedIn: 'root'
})
export class PagosService {

  private API_URL = environment.API_URL;

  constructor(private http: HttpClient) { }

  getPagos(metodoVisualizacion: string, tipoMovimiento: string): Observable<object[]> {
      const params = new HttpParams().set('metodoVisualizacion', metodoVisualizacion).set('tipoMovimiento', tipoMovimiento);
      return this.http.get(this.API_URL + '/pagos.php', {params}).pipe(
        map((res) => res['pagos'])
      );
  }

  getDNIs(): Observable<Jugador[]> {
    return this.http.get(this.API_URL + '/pagos.php?dnis=true').pipe(
      map(res => res['jugadores'])
    );
  }

  getListadoPagos(tipoMovimiento: string) {
    const params = new HttpParams().set('paymentType', tipoMovimiento);
    return this.http.get(this.API_URL + '/pagos_listado.php', {params}).pipe(
      map((res) => res)
    );
  }

  update(pago: Pago) {
    return this.http.put(this.API_URL + '/pagos.php', pago).pipe(
      map((res) => res),
      catchError(this.updatePagoError)
    );
  }

  store(pago: Pago) {
    return this.http.post(this.API_URL + '/pagos.php', pago).pipe(
      map((res) => res),
      catchError(this.storePagoError)
    );
  }

  assign(id: number) {
    return this.http.put(this.API_URL + '/pagos.php', {idAssign: id}).pipe(
        map((res) => res),
        catchError(this.updatePagoError)
    );
  }

  private updatePagoError(error: HttpErrorResponse) {
      // return an observable with a user friendly message
      return throwError('Error al modificar el pago.');
  }

  private storePagoError(error: HttpErrorResponse) {
      // return an observable with a user friendly message
      return throwError('Error al crear el pago.');
  }
}
