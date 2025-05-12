import { Jugador } from './jugador';

export class JugadorCampus extends Jugador {
  // Información de las semanas en las que está inscrito el jugador
  semanas: {
    id_semana: number,
    fecha_inicio: string,
    fecha_fin: string,
    precio: number,
    fecha_inscripcion: string
  }[];
}