import {AfterViewInit, Component, ElementRef, Inject, OnInit, ViewChild} from '@angular/core';
import {Jugador} from '../../../classes/jugador';
import {Familiar} from '../../../classes/familiar';
import {MAT_DIALOG_DATA, MatDialog, MatPaginator, MatSort} from '@angular/material';
import {JugadoresService} from '../../jugadores/jugadores.service';
import {ConfirmDialogComponent} from '../confirm-dialog/confirm-dialog.component';
import {ErrorDialogComponent} from '../error-dialog/error-dialog.component';
import {FamiliaresService} from '../../familiares/familiares.service';
import {TiposParentesco} from '../../../classes/tipos_parentesco';
import {EditarFamiliarDialogComponent} from '../editar-familiar-dialog/editar-familiar-dialog.component';
import {Sexo} from '../../../classes/sexo';
import {GlobalService} from '../../global.service';

@Component({
  selector: 'app-familiares-dialog',
  templateUrl: './familiares-dialog.component.html',
  styleUrls: ['./familiares-dialog.component.css']
})
export class FamiliaresDialogComponent implements OnInit, AfterViewInit {

  @ViewChild(MatPaginator) paginator: MatPaginator;
  @ViewChild(MatSort) sort: MatSort;
  @ViewChild('filtrar') input: ElementRef;

  anyadirFamiliar = false;

  // Objecte familiar, que servirà per crear un de nou
  familiarParaCrear: Familiar = new Familiar();

  familiaresAssignados: Familiar[];
  jugadores: Jugador[];

  // Array de parentescos per mostrar en el modal a l'hora de seleccionar un nou familiar
  tiposParentesco: TiposParentesco[] = [];
  parentescoSeleccionado: number;

  // Array de sexes
  sexos: Sexo[] = [];

  constructor(@Inject(MAT_DIALOG_DATA) public data,
              private jugadoresService: JugadoresService,
              public dialog: MatDialog,
              private familiaresService: FamiliaresService,
              private globalService: GlobalService) { }

  ngOnInit() {
    this.jugadoresService.getFamiliares(this.data.jugador.id).subscribe((res) => this.familiaresAssignados = res);
    this.familiaresService.getTipos().subscribe(items => this.tiposParentesco = items);
    this.familiarParaCrear.telefono1 = this.data.jugador.telefono1;
    this.familiarParaCrear.telefono2 = this.data.jugador.telefono2;
    this.familiarParaCrear.direccion = this.data.jugador.direccion;
    this.familiarParaCrear.codigo_postal = this.data.jugador.codigo_postal;
    this.familiarParaCrear.localidad = this.data.jugador.localidad;
    this.familiarParaCrear.email = this.data.jugador.email;
    // Obtenim els sexes
    this.globalService.getSexos().subscribe((res) => this.sexos = res);
  }

  ngAfterViewInit() {
  }

  public store() {

    this.familiaresService.store(this.familiarParaCrear)
      .subscribe(
        (res) => {
          this.familiaresService.setFamiliar(res['ultimId'], this.data.jugador.id, this.parentescoSeleccionado).subscribe(
            () => {
              this.ngOnInit();
              this.ngAfterViewInit();
            },
            (err) => {
              let dialogRef = this.dialog.open(ErrorDialogComponent, {
                disableClose: false,
                width: '400px'
              });
              dialogRef.componentInstance.errorMessage = err;
              dialogRef.afterClosed().subscribe(() => dialogRef = null);
            }
          );
        },
        (err) => {
          let dialogRef = this.dialog.open(ErrorDialogComponent, {
            disableClose: false,
            width: '400px'
          });
          dialogRef.componentInstance.errorMessage = err;
          dialogRef.afterClosed().subscribe(() => dialogRef = null);
        }
      );
  }

  editFamiliar(familiar: Familiar) {
    this.dialog.open(EditarFamiliarDialogComponent, {
      data: {familiar},
      width: '800px',
      minHeight: '700px',
      disableClose: true
    });
  }

  removeFamiliar(jugador: Jugador, familiar: Familiar) {
    let dialogRef = this.dialog.open(ConfirmDialogComponent, {
      disableClose: false,
      width: '400px'
    });
    dialogRef.componentInstance.confirmMessage = '¿Eliminar ' + familiar.nombre + ', como ' + familiar.tipo_parentesco + '?';

    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.jugadoresService.removeFamiliar(jugador.id, familiar.id).subscribe(
          () => {
              setTimeout(() => {
                this.ngOnInit();
                this.ngAfterViewInit();
                this.anyadirFamiliar = false;
              });
          },
          (err) => {
            let dialogRef2 = this.dialog.open(ErrorDialogComponent, {
              disableClose: false,
              width: '400px'
            });
            dialogRef2.componentInstance.errorMessage = err;
            dialogRef2.afterClosed().subscribe(() => dialogRef2 = null);
          }
        );
      }
      dialogRef = null;
    });
  }

}
