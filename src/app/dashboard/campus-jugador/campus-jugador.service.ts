import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { environment } from '../../../environments/environment';
import { JugadorCampus } from '../../classes/jugador-campus';

@Injectable({
  providedIn: 'root'
})
export class CampusJugadorService {

  private API_URL = environment.API_URL;

  constructor(private http: HttpClient) { }

  /**
   * Obtiene los jugadores inscritos en el Casal d'Estiu con sus semanas
   * @param filtroNombre Filtro opcional por nombre o apellidos
   * @param filtroSemana Filtro opcional por semana específica
   */
  getJugadoresCampus(filtroNombre: string = '', filtroSemana: number = 0): Observable<JugadorCampus[]> {
    let params = new HttpParams();
    
    if (filtroNombre) {
      params = params.set('filtroNombre', filtroNombre);
    }
    
    if (filtroSemana > 0) {
      params = params.set('filtroSemana', filtroSemana.toString());
    }
    
    return this.http.get(this.API_URL + '/campus_jugador.php', { params }).pipe(
      map((res: any) => res.jugadores)
    );
  }
}