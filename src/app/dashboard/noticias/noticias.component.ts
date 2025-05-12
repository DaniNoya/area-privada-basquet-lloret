import {Component, ElementRef, OnInit, ViewChild} from '@angular/core';
import {TemporadasService} from '../temporadas/temporadas.service';
import {Equipo} from '../../classes/equipo';
import {ErrorDialogComponent} from '../dialogs/error-dialog/error-dialog.component';
import {MatDialog} from '@angular/material/dialog';
import {AngularEditorConfig} from '@kolkov/angular-editor';
import {Noticia} from '../../classes/noticia';
import {NoticiasService} from './noticias.service';
import {ConfirmDialogComponent} from '../dialogs/confirm-dialog/confirm-dialog.component';
import {ImageResult, ResizeOptions} from 'ng2-imageupload';
import {MatCheckbox} from '@angular/material/checkbox';
import { NgxSpinnerService } from 'ngx-spinner';
import {AppComponent} from '../../app.component';
import {Media} from "../../classes/media";

@Component({
  selector: 'app-noticias',
  templateUrl: './noticias.component.html',
  styleUrls: ['./noticias.component.css']
})
export class NoticiasComponent implements OnInit {

  spinnerMessage = '';
  not: Noticia = new Noticia();
  editNoticia: Noticia = new Noticia();
  editar = false;
  tipoNoticia: string;
  equipos: Equipo[] = [];
  @ViewChild('titol') titol: ElementRef;
  @ViewChild('editTitol') editTitol: ElementRef;
  @ViewChild('file') file: ElementRef;
  @ViewChild('files') media: ElementRef;
  @ViewChild('video') video: ElementRef;
  @ViewChild('videoEdit') videoEdit: ElementRef;
  @ViewChild('facebook') publishFacebook: MatCheckbox;
  @ViewChild('instagram') publishInstagram: MatCheckbox;
  @ViewChild('twitter') publishTwitter: MatCheckbox;
  editorConfig: AngularEditorConfig = {
    editable: true,
    spellcheck: true,
    height: 'auto',
    minHeight: '0',
    maxHeight: 'auto',
    width: 'auto',
    minWidth: '0',
    enableToolbar: true,
    showToolbar: true,
    placeholder: 'Contenido...',
    sanitize: false,
    toolbarPosition: 'top',
  };

  // Mides màximes per redimensionar fotos
  resizeOptions: ResizeOptions = {
    resizeMaxHeight: 1024,
    resizeMaxWidth: 1024
  };

  noticiasDescargadas: Noticia[] = [];
  msgNoticiesEmpty = false;

  constructor(private temporadaService: TemporadasService,
              private noticiasService: NoticiasService,
              public appComponent: AppComponent,
              public dialog: MatDialog,
              private spinner: NgxSpinnerService) { }

  ngOnInit() {
    this.temporadaService.getEquipos().subscribe(res => this.equipos = res);
    this.not.media = new Array<Media>();
  }

  selected(imageResult: ImageResult) {
    const srcImage = imageResult.resized
      && imageResult.resized.dataURL
      || imageResult.dataURL;
    if (this.editar) {
      this.editNoticia.imagenPortada = srcImage;
    } else {
      this.not.imagenPortada = srcImage;
    }
    this.file.nativeElement.value = '';
  }

  insertMedia(imageResult: ImageResult) {
    let existsInArray = false;
    const srcImage = imageResult.resized
      && imageResult.resized.dataURL
      || imageResult.dataURL;
    if (this.editar) {
      this.editNoticia.media.forEach((v, k) => {
        if (v.mediaURL === srcImage) {
          existsInArray = true;
        }
      });
    } else {
      this.not.media.forEach((v, k) => {
        if (v.mediaURL === srcImage) {
          existsInArray = true;
        }
      });
    }
    if (!existsInArray) {
      if (this.not.media.length <= 2) {
        const media = {filename: imageResult.file.name, filetype: 'photo', tipoMedia: 'data', mediaURL: srcImage};
        (this.editar) ? this.editNoticia.media.push(media) : this.not.media.push(media);
      } else {
        let dialogRef = this.dialog.open(ErrorDialogComponent, {
          disableClose: false,
          width: '400px'
        });
        dialogRef.componentInstance.errorMessage = 'Máximo 3 imágenes adicionales';
        dialogRef.afterClosed().subscribe(() => dialogRef = null);
      }
    } else {
      let dialogRef = this.dialog.open(ErrorDialogComponent, {
        disableClose: false,
        width: '400px'
      });
      dialogRef.componentInstance.errorMessage = 'Imagen <b>' + imageResult.file.name + '</b> ya seleccionada';
      dialogRef.afterClosed().subscribe(() => dialogRef = null);
    }
    this.media.nativeElement.value = '';
    /*if (imageResult.file.type.startsWith('video')) {
      const vid = document.createElement('video');
      vid.height = 240;
      vid.width = 320;
      vid.src = srcImage;
      vid.controls = true;
      if (this.editar) {
        let child = this.videoEdit.nativeElement.lastElementChild;
        while (child) {
          this.videoEdit.nativeElement.removeChild(child);
          child = this.videoEdit.nativeElement.lastElementChild;
        }
        this.videoEdit.nativeElement.appendChild(vid);
        this.editNoticia.video = srcImage;
      } else {
        let child = this.video.nativeElement.lastElementChild;
        while (child) {
          this.video.nativeElement.removeChild(child);
          child = this.video.nativeElement.lastElementChild;
        }
        this.video.nativeElement.appendChild(vid);
        this.not.video = srcImage;
      }
    } else {*/
  }

  deleteImagen(imagen) {
    if (this.editar) {
      this.editNoticia.media.forEach((v, k) => {
        if (v === imagen) {
          this.editNoticia.media.splice(k, 1);
        }
      });
    } else {
      this.not.media.forEach((v, k) => {
        if (v === imagen) {
          this.not.media.splice(k, 1);
        }
      });
    }
  }

  save() {
    this.spinnerMessage = 'Guardando y publicando...';
    this.spinner.show();
    this.not.titol = this.titol.nativeElement.value;
    this.noticiasService.save(this.not, this.publishFacebook.checked, this.publishInstagram.checked, this.publishTwitter.checked).subscribe(
      (res) => {
        if (res['resultat'] === 'ERROR') {
          let dialogRef = this.dialog.open(ErrorDialogComponent, {
            disableClose: false,
            width: '400px'
          });
          dialogRef.componentInstance.errorMessage = res['causa'];
          dialogRef.afterClosed().subscribe(() => dialogRef = null);
        }
        this.not = new Noticia();
        this.not.media = new Array<Media>();
        this.titol.nativeElement.value = '';
        this.publishFacebook.checked = this.publishTwitter.checked = this.publishInstagram.checked = false;
        this.spinner.hide();
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

  descargarNoticias(tipo: string) {
    this.noticiasService.getNoticias(tipo).subscribe(res => {
      this.noticiasDescargadas = res;
      this.msgNoticiesEmpty = this.noticiasDescargadas.length === 0;
    });
  }

  edit(noticia: Noticia) {
    this.spinnerMessage = 'Guardando...';
    this.spinner.show();
    noticia.titol = this.editTitol.nativeElement.value;
    this.noticiasService.edit(noticia).subscribe(
      (res) => {
        this.spinner.hide();
        if (res['resultat'] === 'ERROR') {
          let dialogRef = this.dialog.open(ErrorDialogComponent, {
            disableClose: false,
            width: '400px'
          });
          dialogRef.componentInstance.errorMessage = res['causa'];
          dialogRef.afterClosed().subscribe(() => dialogRef = null);
        } else {
          this.editNoticia = undefined;
          this.editar = false;
        }
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

  delete(noticia: Noticia) {
    const dialogRef = this.dialog.open(ConfirmDialogComponent, {
      disableClose: false,
      width: '400px'
    });
    dialogRef.componentInstance.confirmMessage = '¿Eliminar noticia ' + noticia.titol + '?';

    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.spinnerMessage = 'Eliminando ...';
        this.spinner.show();
        this.noticiasService.delete(noticia).subscribe(
          (res) => {
            this.spinner.hide();
            if (res['resultat'] === 'ERROR') {
              let dialogRef2 = this.dialog.open(ErrorDialogComponent, {
                disableClose: false,
                width: '400px'
              });
              dialogRef2.componentInstance.errorMessage = res['causa'];
              dialogRef2.afterClosed().subscribe(() => dialogRef2 = null);
            } else {
              this.descargarNoticias(noticia.tipo);
            }
          },
          (err) => {
            this.spinner.hide();
            let dialogRef2 = this.dialog.open(ErrorDialogComponent, {
              disableClose: false,
              width: '400px'
            });
            dialogRef2.componentInstance.errorMessage = err;
            dialogRef2.afterClosed().subscribe(() => dialogRef2 = null);
          }
        );
      }
    });
  }

  xarxesSocials(noticia: Noticia, xarxa: string) {
    this.spinnerMessage = 'Actualizando ' + xarxa + '...';
    this.spinner.show();
    this.noticiasService.enableDisableXarxes(noticia, xarxa).subscribe(
      (res) => {
        this.spinner.hide();
        if (res['resultat'] === 'ERROR') {
          let dialogRef = this.dialog.open(ErrorDialogComponent, {
            disableClose: false,
            width: '400px'
          });
          dialogRef.componentInstance.errorMessage = res['causa'];
          dialogRef.afterClosed().subscribe(() => dialogRef = null);
        } else {
          this.descargarNoticias(noticia.tipo);
        }
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
