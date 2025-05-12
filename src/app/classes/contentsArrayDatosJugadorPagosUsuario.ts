import {ContentsArrayPagosUsuario} from '../classes/contentsArrayPagosUsuario';
import {ContentsArrayDescuentos} from '../classes/contentsArrayDescuentosPagosUsuario';
export class ContentsArrayDatosJugador {
    mas18: boolean;
    idJugador: string;
    jugador: string;
    pagos: ContentsArrayPagosUsuario[];
    descuentos: ContentsArrayDescuentos[];
    importesSelect: number[];
    restante: number;
}