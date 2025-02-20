import {Component, ElementRef, Inject, OnInit, ViewChild} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialog, MatDialogRef} from '@angular/material';
import {CrearTemporadaComponent} from '../crear-temporada/crear-temporada.component';
import {GlobalService} from '../../global.service';
import {Categoria} from '../../../classes/categoria';
import {Competicion} from '../../../classes/competicion';
import {TipoCategoria} from '../../../classes/tipo_categoria';
import {Equipo} from '../../../classes/equipo';
import {TemporadasService} from '../../temporadas/temporadas.service';
import {ErrorDialogComponent} from '../error-dialog/error-dialog.component';
import {ConfirmDialogComponent} from '../confirm-dialog/confirm-dialog.component';
import { NgxSpinnerService } from 'ngx-spinner';
import {ImageResult, ResizeOptions} from 'ng2-imageupload';

@Component({
  selector: 'app-crear-equipo',
  templateUrl: './crear-equipo.component.html',
  styleUrls: ['./crear-equipo.component.css']
})
export class CrearEquipoComponent implements OnInit {

  categorias: Categoria[] = [];
  competiciones: Competicion[] = [];
  tiposCategoria: TipoCategoria[] = [];

  equipo: Equipo = new Equipo();
  temporada;
  editarEquipo = false;
  @ViewChild('file') file: ElementRef;

  // Mides màximes per redimensionar fotos
  resizeOptions: ResizeOptions = {
    resizeMaxHeight: 1024,
    resizeMaxWidth: 1024
  };

  constructor(@Inject(MAT_DIALOG_DATA) public data,
              public dialog: MatDialogRef<CrearTemporadaComponent>,
              public dialogRef: MatDialog,
              private globalService: GlobalService,
              private temporadaService: TemporadasService,
              private spinner: NgxSpinnerService) { }

  ngOnInit() {
    this.temporadaService.getTemporadas().subscribe( (res) => {
      this.equipo.id_temporada = res[0]['id'];
      this.temporada = res[0]['temporada'];
    });
    this.equipo.nacidos_desde_anyo = 2000;
    this.equipo.nacidos_hasta_anyo = 2002;
    if (this.data) {
      this.equipo = this.data.equipo;
      this.editarEquipo = true;
    }
    this.globalService.getCategorias().subscribe((res) => this.categorias = res);
    this.globalService.getCompeticiones().subscribe((res) => this.competiciones = res);
    this.globalService.getTiposCategorias().subscribe((res) => this.tiposCategoria = res);
  }

  saveEquipo() {
    this.spinner.show();
    if (this.editarEquipo) {
      this.temporadaService.updateEquipo(this.equipo).subscribe(
        () => {
          this.dialog.close();
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
          });
        }
      );
    } else {
      this.temporadaService.storeEquipo(this.equipo).subscribe(
        () => {
          this.dialog.close();
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
          });
        }
      );
    }
  }

  eliminarEquipo() {
    const dialogRef = this.dialogRef.open(ConfirmDialogComponent, {
      disableClose: false,
      width: '400px'
    });
    dialogRef.componentInstance.confirmMessage = '¿Eliminar equipo ' + this.equipo.categoria + ' ' + this.equipo.tipoCategoria + ' ' + this.equipo.descripcion + '?';

    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.spinner.show();
        this.temporadaService.eliminarEquipo(this.equipo).subscribe(
          () => {
            this.dialog.close();
            this.spinner.hide();
          },
          (err) => {
            this.spinner.hide();
            let dialogRef2 = this.dialogRef.open(ErrorDialogComponent, {
              disableClose: false,
              width: '400px'
            });
            dialogRef2.componentInstance.errorMessage = err;
            dialogRef2.afterClosed().subscribe(() => {
              dialogRef2 = null;
            });
          }
        );
      }
    });
    }

  selected(imageResult: ImageResult) {
    this.equipo.foto = imageResult.resized
      && imageResult.resized.dataURL
      || imageResult.dataURL;
    this.file.nativeElement.value = '';
  }
}
