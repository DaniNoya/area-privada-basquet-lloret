import {Component, ElementRef, Inject, OnInit, ViewChild} from '@angular/core';
import {Sexo} from '../../../classes/sexo';
import {MAT_DIALOG_DATA, MatDialog} from '@angular/material';
import {GlobalService} from '../../global.service';
import {EntrenadoresService} from '../../entrenadores/entrenadores.service';
import {NivelFormacion} from '../../../classes/nivel_formacion';
import {ErrorDialogComponent} from '../error-dialog/error-dialog.component';
import {NgxSpinnerService} from 'ngx-spinner';
import {ImageResult, ResizeOptions} from 'ng2-imageupload';

@Component({
  selector: 'app-editar-entrenador-dialog',
  templateUrl: './editar-entrenador-dialog.component.html',
  styleUrls: ['./editar-entrenador-dialog.component.css']
})
export class EditarEntrenadorDialogComponent implements OnInit {

  // Array de sexes
  sexos: Sexo[] = [];

  // Array de nivells de formació
  nivelesFormacion: NivelFormacion[] = [];

  // Variable on mostrarem l'error, en cas que n'hi hagi
  error: string;

  // Mides màximes per redimensionar fotos
  resizeOptions: ResizeOptions = {
    resizeMaxHeight: 300,
    resizeMaxWidth: 300
  };
  @ViewChild('file') file: ElementRef;

  constructor(@Inject(MAT_DIALOG_DATA) public data,
              public dialog: MatDialog,
              private globalService: GlobalService,
              private entrenadoresService: EntrenadoresService,
              public spinner: NgxSpinnerService) { }

  ngOnInit() {
    // Obtenim els sexes
    this.globalService.getSexos().subscribe((res) => this.sexos = res);
    // Subscripció als nivells de formació
    this.entrenadoresService.getNiveles().subscribe((res) => this.nivelesFormacion = res);
  }

  public save() {
    this.error = '';
    this.spinner.show();
    this.entrenadoresService.update(this.data.entrenador)
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

  selected(imageResult: ImageResult) {
    this.data.entrenador.foto = imageResult.resized
      && imageResult.resized.dataURL
      || imageResult.dataURL;
    this.file.nativeElement.value = '';
  }
}
