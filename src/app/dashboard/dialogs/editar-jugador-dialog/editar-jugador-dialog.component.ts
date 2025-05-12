import {Component, Inject, OnInit} from '@angular/core';
import {Sexo} from '../../../classes/sexo';
import {MAT_DIALOG_DATA, MatDialog} from '@angular/material';
import {GlobalService} from '../../global.service';
import {JugadoresService} from '../../jugadores/jugadores.service';
import {NgxSpinnerService} from 'ngx-spinner';

import {Jugador} from '../../../classes/jugador';
import {PagosUsuarioDialogComponent} from '../pagos-usuario-dialog/pagos-usuario-dialog.component';

@Component({
  selector: 'app-editar-jugador-dialog',
  templateUrl: './editar-jugador-dialog.component.html',
  styleUrls: ['./editar-jugador-dialog.component.css']
})
export class EditarJugadorDialogComponent implements OnInit {

  // Objecte jugador, que servirà de pivot per tractar les dades
  jugadorSeleccionado: Jugador;

  // Array de sexes
  sexos: Sexo[] = [];

  // Variable on mostrarem l'error, en cas que n'hi hagi
  error: string;

  constructor(@Inject(MAT_DIALOG_DATA) public data,
              public dialog: MatDialog,
              private globalService: GlobalService,
              private jugadoresService: JugadoresService,
              public spinner: NgxSpinnerService) { }

  ngOnInit() {
    this.jugadoresService.getJugador(this.data.jugador.id).subscribe((res) => {
      this.jugadorSeleccionado = res;
      //console.log(this.jugadorSeleccionado);
    });
    
    // Obtenim els sexes
    this.globalService.getSexos().subscribe((res) => this.sexos = res);
  }

  startPagosUsuario() {
    this.error = '';
    //console.log(this.jugadorSeleccionado);
    let modalConta = this.dialog.open(PagosUsuarioDialogComponent, {
      data: {jugador: this.jugadorSeleccionado},
      width: '900px',
      minHeight: '700px',
      maxHeight: '900px',
      disableClose: true
    });

    modalConta.afterClosed().subscribe((res) => {
      if(res.quotes){
        this.jugadorSeleccionado.quotes = res.quotes;
      }
    });
  }

  public save() {
    this.error = '';
    this.spinner.show();
    this.jugadoresService.update(this.data.jugador)
      .subscribe(
        () => {
          document.getElementById('close').click();
          this.spinner.hide();
        },
        (err) => {
          this.error = err;
          this.spinner.hide();
        }
      );
  }
}
