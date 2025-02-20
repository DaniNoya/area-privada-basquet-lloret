import {Component, OnInit} from '@angular/core';
import {MatDialog, MatDialogRef} from '@angular/material';
import {FormControl} from '@angular/forms';
import {Temporada} from '../../../classes/temporada';
import {formatDate} from '@angular/common';
import {TemporadasService} from '../../temporadas/temporadas.service';
import {ErrorDialogComponent} from '../error-dialog/error-dialog.component';
import { NgxSpinnerService } from 'ngx-spinner';

@Component({
  selector: 'app-crear-temporada',
  templateUrl: './crear-temporada.component.html',
  styleUrls: ['./crear-temporada.component.css']
})
export class CrearTemporadaComponent implements OnInit {

  fechaInicio = new FormControl(new Date());
  fechaFin = new FormControl();
  observaciones = new FormControl();
  temporada: Temporada = new Temporada();

  constructor(public dialog: MatDialogRef<CrearTemporadaComponent>,
              public dialogRef: MatDialog,
              private temporadaService: TemporadasService,
              private spinner: NgxSpinnerService) { }

  ngOnInit() {
    const fechaFinDate = new Date();
    fechaFinDate.setDate(fechaFinDate.getDate() - 1);
    fechaFinDate.setFullYear(fechaFinDate.getFullYear() + 1);
    this.fechaFin.setValue(fechaFinDate);
  }

  actualizarFechaFin() {
    const fechaFinDate = new Date(this.fechaInicio.value);
    fechaFinDate.setDate(fechaFinDate.getDate() - 1);
    fechaFinDate.setFullYear(fechaFinDate.getFullYear() + 1);
    this.fechaFin.setValue(fechaFinDate);
  }

  saveTemporada() {
    this.spinner.show();
    this.temporada.fecha_inicio = formatDate(this.fechaInicio.value, 'yyyy-MM-dd', 'en-US');
    this.temporada.fecha_final = formatDate(this.fechaFin.value, 'yyyy-MM-dd', 'en-US');
    this.temporada.temporada = this.fechaInicio.value.getFullYear() + ' - ' + this.fechaFin.value.getFullYear();
    this.temporada.observaciones = this.observaciones.value;
    this.temporadaService.store(this.temporada)
      .subscribe(
        () => {
          this.dialog.close(true);
          this.spinner.hide();
        },
        (err) => {
          this.spinner.hide();
          let dialogRef = this.dialogRef.open(ErrorDialogComponent, {
            disableClose: false,
            width: '400px'
          });
          dialogRef.componentInstance.errorMessage = err;
          dialogRef.afterClosed().subscribe(() => {
            dialogRef = null;
            this.dialog.close(false);
          });
        }
      );
  }
}
