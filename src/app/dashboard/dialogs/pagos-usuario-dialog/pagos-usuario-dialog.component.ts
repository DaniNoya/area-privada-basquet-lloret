import {AfterViewInit, Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialog, MatDialogRef} from '@angular/material';
import {GlobalService} from '../../global.service';
import {Jugador} from '../../../classes/jugador';
import {TipoPago} from '../../../classes/tipo_pago';
import {Pago} from "../../../classes/pago";
import {PagosUsuarioService} from '../../../dashboard/pagos-usuario/pagos-usuario.service';
import {ContentsArrayFinal} from "../../../classes/contentsArrayFinalPagosUsuario";
import {ValidarPermisoComponent} from '../validar-permiso/validar-permiso.component';

@Component({
  selector: 'app-pagos-usuario-dialog',
  templateUrl: './pagos-usuario-dialog.component.html',
  styleUrls: ['./pagos-usuario-dialog.component.css']
})
export class PagosUsuarioDialogComponent implements OnInit, AfterViewInit {

  // Objecte jugador, que servirà de pivot per tractar les dades
  jugadorSeleccionado: Jugador;

  // Array de pagos
  arrayFinal: ContentsArrayFinal[];

  // Objecte pago, que servirà de pivot per tractar les dades
  pagoUsuarioSeleccionado: ContentsArrayFinal;

  // Mode de visualització per defecte
  metodoVisualizacion = 'todo';

  // Array de tipo pago
  tiposPago: TipoPago[] = [];

  resValidPermiso = false;

  constructor(@Inject(MAT_DIALOG_DATA) public data,
              private pagosUsuarioService: PagosUsuarioService,
              public dialog: MatDialog,
              public dialogRef: MatDialogRef<PagosUsuarioDialogComponent>,
              private globalService: GlobalService) { }

  ngOnInit() {
    this.pagosUsuarioService.getPagos(this.data.jugador.id, this.metodoVisualizacion).subscribe(res => {
      this.arrayFinal = res as ContentsArrayFinal[];
      /*for(let tipo of this.arrayFinal) {
        for(let persona of tipo.data) {
          persona.importesSelect = [];
          for (let i = 50; i <= persona.restante; i += 50) {
            if((i + 50) <= persona.restante){
              persona.importesSelect.push(i);
            }
          }
          persona.importesSelect.push(persona.restante);
        }
      }*/
      //console.log(this.arrayFinal);
    });
    //console.log(this.data.jugador);
    // Obtenim los tipos de pago
    this.globalService.getTiposPago().subscribe((res) => this.tiposPago = res);
  }

  ngAfterViewInit() {
  }

  getPagosFiltered(arr: Pago[], idTipoPago: number): Pago[] {
    return arr.filter(p => p.tipoPago == idTipoPago);
  }

  getQuota(jugador: Jugador, idTipoPago: number): Pago[] {
    const ret: Pago[] = [];
    const quota = jugador.quotes.find(q => q.idTipo == idTipoPago);

    if(quota == null){
      const p = new Pago();
      p.importe = 0;
      ret.push(p);
      return ret;
    } else {
      const p = new Pago();
      p.importe = quota.quota;
      ret.push(p);
      return ret;
    }
  }

  getRestante(jugador: Jugador, idTipoPago: number): Pago[] {
    const ret: Pago[] = [];
    const quota = jugador.quotes.find(q => q.idTipo == idTipoPago);

    if(quota == null){
      const p = new Pago();
      p.importe = 0;
      ret.push(p);
      return ret;
    } else {
      //if (quota.quota - quota.pagado > 0) {}
      const p = new Pago();
      p.importe = quota.quota - quota.pagado;
      ret.push(p);
      return ret;
    }
  }

  checkHavePagos(arr: Pago[], idTipoPago: number): boolean {
    return arr.some(p => p.tipoPago == idTipoPago);
  }

  checkHaveInscripcion(jugador: Jugador, idTipoPago: number): boolean {
    if(jugador.quotes){
      return jugador.quotes.some(q => q.idTipo == idTipoPago);
    }

    return false;
  }

  editCuota(jugador: Jugador, idTipoPago: number) {
    let modalValid = this.dialog.open(ValidarPermisoComponent, {
      data: {jugador: jugador, tipoPago: idTipoPago},
      width: '437px',
      minHeight: '230px',
      maxHeight: '230px',
      disableClose: true
    });

    modalValid.afterClosed().subscribe((res) => {
      if(res == true){
        this.resValidPermiso = true;
      } else {
        this.resValidPermiso = false;
      }
    });
  }

  updateCuota(idJugador:number, nuevaCuota: number, idTipoPago: number){
    /*console.log(idJugador);
    console.log(nuevaCuota);
    console.log(idTipoPago);*/
    if(nuevaCuota >= 0){
      this.pagosUsuarioService.updateCuota(idJugador, nuevaCuota, idTipoPago).subscribe((res) => {
        this.resValidPermiso = false;

        this.data.jugador = res;
        this.ngOnInit()
      });
    }
  }
}
