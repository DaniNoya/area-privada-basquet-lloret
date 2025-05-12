import {AfterViewInit, Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialog, MatDialogRef} from '@angular/material';
import {GlobalService} from '../../global.service';
import {Jugador} from '../../../classes/jugador';
import {Pago} from "../../../classes/pago";
import {PagosUsuarioService} from '../../../dashboard/pagos-usuario/pagos-usuario.service';
import {ErrorDialogComponent} from '../error-dialog/error-dialog.component';

@Component({
  selector: 'app-validar-permiso',
  templateUrl: './validar-permiso.component.html',
  styleUrls: ['./validar-permiso.component.css']
})
export class ValidarPermisoComponent implements OnInit, AfterViewInit {

  valido = false;

  constructor(@Inject(MAT_DIALOG_DATA) public data,
              private pagosUsuarioService: PagosUsuarioService,
              public dialog: MatDialogRef<ValidarPermisoComponent>,
              public dialogError: MatDialog,
              private globalService: GlobalService) { }

  ngOnInit() {
  }

  ngAfterViewInit() {
  }

  checkPassword(passw: string){
    this.globalService.postPassword(passw).subscribe(res => {
      this.valido = res;
      //console.log(this.valido);

      this.dialog.close(this.valido);
      if(this.valido == false){
        let dialogError = this.dialogError.open(ErrorDialogComponent, {
          disableClose: false,
          width: '400px'
        });
        dialogError.componentInstance.errorMessage = 'Contraseña incorrecta';
        dialogError.afterClosed().subscribe(() => {
          dialogError = null;
        });
      }
    });
  }
}
