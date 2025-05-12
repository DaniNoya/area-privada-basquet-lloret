import { Injectable } from '@angular/core';
import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError, map } from 'rxjs/operators';
import { environment } from '../../../environments/environment';
import { Socio } from '../../classes/socio';

@Injectable({
  providedIn: 'root'
})
export class SociosService {

  private API_URL = environment.API_URL;

  constructor(private http: HttpClient) { }

  getSocios(metodoVisualizacion: string): Observable<Socio[]> {
    let params = new HttpParams().set('metodoVisualizacion', metodoVisualizacion)
    
    return this.http.get(this.API_URL + '/socios.php', {params}).pipe(
      map((res) => res['socios'])
    );
  }

  update(socio: Socio) {
    return this.http.put(this.API_URL + '/socios.php', socio).pipe(
      map((res) => res),
      catchError(this.updateSocioError)
    );
  }

  store(socio: Socio) {
    return this.http.post(this.API_URL + '/socios.php', socio).pipe(
      map((res) => res),
      catchError(this.storeSocioError)
    );
  }

  private updateSocioError(error: HttpErrorResponse) {
    // return an observable with a user friendly message
    return throwError('Error al modificar el socio.');
  }

  private storeSocioError(error: HttpErrorResponse) {
    // return an observable with a user friendly message
    return throwError('Error al crear el socio.');
  }
}
