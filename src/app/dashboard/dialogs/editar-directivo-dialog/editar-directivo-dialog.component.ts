import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialog} from "@angular/material/dialog";
import {GlobalService} from "../../global.service";
import {EntrenadoresService} from "../../entrenadores/entrenadores.service";
import {NgxSpinnerService} from "ngx-spinner";
import {DirectivosService} from "../../directivos/directivos.service";
import {Cargo} from "../../../classes/cargo";
import {Sexo} from "../../../classes/sexo";

@Component({
  selector: 'app-editar-directivo-dialog',
  templateUrl: './editar-directivo-dialog.component.html',
  styleUrls: ['./editar-directivo-dialog.component.css']
})
export class EditarDirectivoDialogComponent implements OnInit {

  // Array de Càrrecs
  cargos: Cargo[] = [];

  // Array de sexes
  sexos: Sexo[] = [];

  // Variable on mostrarem l'error, en cas que n'hi hagi
  error: string;

  constructor(@Inject(MAT_DIALOG_DATA) public data,
              public dialog: MatDialog,
              private globalService: GlobalService,
              private directivosService: DirectivosService,
              public spinner: NgxSpinnerService) { }

  ngOnInit() {
    // Obtenim els sexes
    this.globalService.getSexos().subscribe((res) => this.sexos = res);
    // Subscripció als nivells de formació
    this.directivosService.getCargos().subscribe((res) => this.cargos = res);
  }

  public save() {
    this.error = '';
    this.spinner.show();
    this.directivosService.update(this.data.directivo)
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
