export class Pago {
    id: number;
    idDatosIntermedios: number;
    dniTutor: string;
    tutor: string;
    dniJugador: string;
    jugador: string;
    idTransaccion: string;
    fechaTransaccion: string;
    tipoPago: number;
    tipoPagoDescripcion: string;
    descripcion: string;
    importe: number;
    restante: number;
    pagoManual: number;
    pagoCompletado: number;
    pagoModificable: number;
}
