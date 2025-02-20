import {AfterViewInit, Component, ElementRef, Inject, OnInit, ViewChild} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialog, MatPaginator, MatSort, MatTableDataSource} from '@angular/material';
import {TemporadasService} from '../../temporadas/temporadas.service';
import {Jugador} from '../../../classes/jugador';
import {ErrorDialogComponent} from '../error-dialog/error-dialog.component';
import {NgxSpinnerService} from 'ngx-spinner';
import {ImageResult, ResizeOptions} from 'ng2-imageupload';

@Component({
  selector: 'app-editar-dorsales',
  templateUrl: './editar-dorsales.component.html',
  styleUrls: ['./editar-dorsales.component.css']
})
export class EditarDorsalesComponent implements OnInit, AfterViewInit {

  isLoadingResults = true;

  displayedColumns: string[] = ['nombre', 'primer_apellido', 'segundo_apellido', 'dorsal', 'foto'];
  dataSource: MatTableDataSource<Jugador> = new MatTableDataSource<Jugador>();
  @ViewChild(MatSort) sort: MatSort;
  @ViewChild('file') file: ElementRef;
  sortActive: string;
  sortDirection: string;

  // Mides màximes per redimensionar fotos
  resizeOptions: ResizeOptions = {
    resizeMaxHeight: 300,
    resizeMaxWidth: 300
  };

  constructor(@Inject(MAT_DIALOG_DATA) public data,
              public dialog: MatDialog,
              public temporadaService: TemporadasService,
              public spinner: NgxSpinnerService) { }

  ngOnInit() {
    this.isLoadingResults = true;
    this.temporadaService.getJugadores(this.data.equipo).subscribe( (res) => {
      this.dataSource.data = res['jugadoresAsignados'];
    });
    this.sortActive = 'dorsal';
    this.sortDirection = 'asc';
    this.isLoadingResults = false;
  }

  ngAfterViewInit() {
    this.dataSource.sort = this.sort;
  }
  selected(imageResult: ImageResult, jugador: Jugador) {
    jugador.foto = imageResult.resized
      && imageResult.resized.dataURL
      || imageResult.dataURL;
    this.temporadaService.updateFoto(jugador, this.data.equipo).subscribe(
      () => {},
      (err) => {
        let dialogRef = this.dialog.open(ErrorDialogComponent, {
          disableClose: false,
          width: '400px'
        });
        dialogRef.componentInstance.errorMessage = err;
        dialogRef.afterClosed().subscribe(() => dialogRef = null);
      }
    );
    this.file.nativeElement.value = '';
  }

  saveDorsal(jugador: Jugador, dorsal) {
    let repetit = false;
    this.dataSource.data.forEach((j) => {
      if (j.dorsal === dorsal) {
        repetit = true;
      }
    });
    if (repetit && dorsal.length !== 0) {
      let dialogRef = this.dialog.open(ErrorDialogComponent, {
        disableClose: false,
        width: '400px'
      });
      dialogRef.componentInstance.errorMessage = 'Dorsal repetido';
      dialogRef.afterClosed().subscribe(() => {
        dialogRef = null;
        this.ngOnInit();
        this.ngAfterViewInit();
      });
    } else {
      this.spinner.show();
      jugador.dorsal = dorsal;
      this.temporadaService.updateDorsal(jugador, this.data.equipo).subscribe(
        () => {
          this.ngOnInit();
          this.ngAfterViewInit();
          this.spinner.hide();
        },
        (err) => {
          this.spinner.hide();
          let dialogRef = this.dialog.open(ErrorDialogComponent, {
            disableClose: false,
            width: '400px'
          });
          dialogRef.componentInstance.errorMessage = err;
          dialogRef.afterClosed().subscribe(() => {
            dialogRef = null;
            this.ngOnInit();
            this.ngAfterViewInit();
          });
        });
    }
  }
}
