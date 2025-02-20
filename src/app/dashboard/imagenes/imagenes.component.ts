import {Component, ElementRef, OnInit, ViewChild} from '@angular/core';
import {TemporadasService} from '../temporadas/temporadas.service';
import {Equipo} from '../../classes/equipo';
import {ErrorDialogComponent} from '../dialogs/error-dialog/error-dialog.component';
import {MatDialog} from '@angular/material/dialog';
import {ImagenesService} from './imagenes.service';
import {NgxSpinnerService} from 'ngx-spinner';
import {ImageResult, ResizeOptions} from 'ng2-imageupload';

@Component({
  selector: 'app-imagenes',
  templateUrl: './imagenes.component.html',
  styleUrls: ['./imagenes.component.css']
})
export class ImagenesComponent implements OnInit {
  equipos: Equipo[] = [];
  equipoSeleccionado: Equipo;
  imagenes = new Array<string>();
  imagenesDescargadas = new Array<string>();
  fotosClub = false;
  seleccion = '';
  @ViewChild('file') file: ElementRef;

  // Mides màximes per redimensionar fotos
  resizeOptions: ResizeOptions = {
    resizeMaxHeight: 1024,
    resizeMaxWidth: 1024
  };

  constructor(private temporadaService: TemporadasService,
              private imagenesService: ImagenesService,
              private spinner: NgxSpinnerService,
              public dialog: MatDialog) { }

  ngOnInit() {
    this.temporadaService.getEquipos().subscribe(res => this.equipos = res);
  }

  selected(imageResult: ImageResult) {
    let existsInArray = false;
    const srcImage = imageResult.resized
      && imageResult.resized.dataURL
      || imageResult.dataURL;
    this.imagenes.forEach((v, k) => {
      if (v === srcImage) {
        existsInArray = true;
      }
    });
    if (!existsInArray) {
      this.imagenes.push(srcImage);
    } else {
      let dialogRef = this.dialog.open(ErrorDialogComponent, {
        disableClose: false,
        width: '400px'
      });
      dialogRef.componentInstance.errorMessage = 'Imagen <b>' + imageResult.file.name + '</b> ya seleccionada';
      dialogRef.afterClosed().subscribe(() => dialogRef = null);
    }
    this.file.nativeElement.value = '';
  }

  deleteImagen(imagen) {
    this.imagenes.forEach((v, k) => {
      if (v === imagen) {
        this.imagenes.splice(k, 1);
      }
    });
  }

  deleteImagenDescargada(imagen) {
    this.imagenesDescargadas.forEach((v, k) => {
      if (v === imagen) {
        this.imagenesDescargadas.splice(k, 1);
      }
    });
  }

  descargarImatges() {
    this.imagenesDescargadas = [];
    this.seleccion = '';
    if (this.fotosClub) {
      this.imagenesService.getImatgesClub().subscribe(res => {
        this.imagenesDescargadas = res;
        this.seleccion = 'Fotos de club';
      });
    } else {
      this.imagenesService.getImatgesEquip(this.equipoSeleccionado).subscribe(res => {
        this.imagenesDescargadas = res;
        this.seleccion = this.equipoSeleccionado.categoria + ' ' + this.equipoSeleccionado.tipoCategoria + ' ' + this.equipoSeleccionado.descripcion;
      });
    }
  }

  reset() {
    this.imagenes = [];
    this.imagenesDescargadas = [];
    this.seleccion = '';
    this.fotosClub = false;
    this.spinner.hide();
  }

  guardarImatges() {
    this.spinner.show();
    if (this.fotosClub) {
      this.imagenesService.subirAClub(this.imagenes).subscribe(
        () => {
          this.reset();
        },
        (err) => {
          this.spinner.hide();
          let dialogRef = this.dialog.open(ErrorDialogComponent, {
            disableClose: false,
            width: '400px'
          });
          dialogRef.componentInstance.errorMessage = err;
          dialogRef.afterClosed().subscribe(() => dialogRef = null);
        }
      );
    } else {
      this.imagenesService.subirAEquipo(this.equipoSeleccionado, this.imagenes).subscribe(
        () => {
          this.reset();
        },
        (err) => {
          this.spinner.hide();
          let dialogRef = this.dialog.open(ErrorDialogComponent, {
            disableClose: false,
            width: '400px'
          });
          dialogRef.componentInstance.errorMessage = err;
          dialogRef.afterClosed().subscribe(() => dialogRef = null);
        }
      );
    }
  }

  eliminarImatges() {
    this.spinner.show();
    if (this.fotosClub) {
      this.imagenesService.updateClub(this.imagenesDescargadas).subscribe(
        () => {
          this.reset();
        },
        (err) => {
          this.spinner.hide();
          let dialogRef = this.dialog.open(ErrorDialogComponent, {
            disableClose: false,
            width: '400px'
          });
          dialogRef.componentInstance.errorMessage = err;
          dialogRef.afterClosed().subscribe(() => dialogRef = null);
        }
      );
    } else {
      this.imagenesService.updateEquipo(this.equipoSeleccionado, this.imagenesDescargadas).subscribe(
        () => {
          this.reset();
        },
        (err) => {
          this.spinner.hide();
          let dialogRef = this.dialog.open(ErrorDialogComponent, {
            disableClose: false,
            width: '400px'
          });
          dialogRef.componentInstance.errorMessage = err;
          dialogRef.afterClosed().subscribe(() => dialogRef = null);
        }
      );
    }
  }
}
