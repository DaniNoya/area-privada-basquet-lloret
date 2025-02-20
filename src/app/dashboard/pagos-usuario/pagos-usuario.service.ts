import {Injectable} from '@angular/core';
import {HttpClient, HttpErrorResponse, HttpParams} from '@angular/common/http';
import {Router} from '@angular/router';
import {Observable, throwError} from 'rxjs';
import {Pago} from '../../classes/Pago';
import {ContentsArrayFinal} from '../../classes/contentsArrayFinalPagosUsuario';
import {catchError, map} from 'rxjs/operators';
import {environment} from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class PagosUsuarioService {

  private API_URL = environment.API_URL;

  constructor(private http: HttpClient,
              private router: Router) { }

  getPagos(idUsuario: number, metodoVisualizacion: string, exclusiones: string = null): Observable<ContentsArrayFinal[]> {
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
    return this.http.get(this.API_URL + '/pagosUsuario.php', {params}).pipe(
      map((res) => res['arrayFinal'])
    );
  }

  store(idTipo, conceptoTipo, idUsuario: number, idJugador, importe: string) {
    return this.http.post(this.API_URL + '/pagosUsuario.php', {IdTipo: idTipo, ConceptoTipo: conceptoTipo, idUsuario: idUsuario, IdJugador: idJugador, Importe: importe}).pipe(
      map((res) => res['resultat'] as number),
      catchError(this.storePagoError)
    );
  }

  redirectPasarela(params: object) {
    let mapForm = document.createElement("form");
    mapForm.target = "_self";
    mapForm.method = "POST"; // or "post" if appropriate
    mapForm.action = this.API_URL + '/pasarelaPrivada.php';
    Object.keys(params).forEach(function(param){
      let mapInput = document.createElement("input");
      mapInput.type = "hidden";
      mapInput.name = param;
      mapInput.setAttribute("value", params[param]);
      mapForm.appendChild(mapInput);
    });
    document.body.appendChild(mapForm);
    mapForm.submit();
  }

  updateCuota(idJugador: number, nuevaCuota: number, idTipoPago: number){
    return this.http.put(this.API_URL + '/jugadorTemporada.php', {idJugador: idJugador, nuevaCuota: nuevaCuota, idTipoPago: idTipoPago}).pipe(
      map((res) => res['jugador'])
    );
  }

  private storePagoError(error: HttpErrorResponse) {
    // return an observable with a user friendly message
    return throwError('Error al realizar el pago.');
  }
}
