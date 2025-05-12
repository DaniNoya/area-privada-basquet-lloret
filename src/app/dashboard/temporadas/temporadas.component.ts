import {Component, ElementRef, OnInit, ViewChild} from '@angular/core';
import {TemporadasService} from './temporadas.service';
import {Temporada} from '../../classes/temporada';
import {Importes} from '../../classes/Importes';
import {MatDialog, MatSelectionList, MatPaginator, MatSort, MatTableDataSource} from '@angular/material';
import {CrearTemporadaComponent} from '../dialogs/crear-temporada/crear-temporada.component';
import {ConfirmDialogComponent} from '../dialogs/confirm-dialog/confirm-dialog.component';
import {Equipo} from '../../classes/equipo';
import {CrearEquipoComponent} from '../dialogs/crear-equipo/crear-equipo.component';
import {Jugador} from '../../classes/jugador';
import {ErrorDialogComponent} from '../dialogs/error-dialog/error-dialog.component';
import {EditarJugadorDialogComponent} from '../dialogs/editar-jugador-dialog/editar-jugador-dialog.component';
import {EditarDorsalesComponent} from '../dialogs/editar-dorsales/editar-dorsales.component';
import {Entrenador} from '../../classes/entrenador';
import {EditarEntrenadorDialogComponent} from '../dialogs/editar-entrenador-dialog/editar-entrenador-dialog.component';
import {Familiar} from '../../classes/familiar';
import {EditarFamiliarDialogComponent} from '../dialogs/editar-familiar-dialog/editar-familiar-dialog.component';
import {NgxSpinnerService} from 'ngx-spinner';
import {Directivo} from '../../classes/directivo';
import {EditarDirectivoDialogComponent} from '../dialogs/editar-directivo-dialog/editar-directivo-dialog.component';

@Component({
  selector: 'app-temporadas',
  templateUrl: './temporadas.component.html',
  styleUrls: ['./temporadas.component.css']
})
export class TemporadasComponent implements OnInit {

  temporadaActual: string;
  temporadas: Temporada[] = [];

  equipos: Equipo[] = [];

  asignarJugadores = false;
  asignarEntrenadores = false;
  asignarDelegados = false;
  equipoSeleccionado: Equipo;
  jugadoresAsignados: Jugador[] = [];
  entrenadoresAsignados: Entrenador[] = [];
  directivosAsignados: Directivo[] = [];
  familiaresAsignados: Familiar[] = [];
  jugadoresDisponibles: Jugador[] = [];
  entrenadoresDisponibles: Entrenador[] = [];
  directivosDisponibles: Directivo[] = [];
  familiaresDisponibles: Familiar[] = [];
  jugadoresDisponiblesJovenes: Jugador[] = [];

  // Objecte que emmagatzema les dades per la taula
  dataSource = new MatTableDataSource<Importes>();
  // Columnes que mostrarem a la taula
  displayedColumns: string[] = ['conceptoVisible', 'importe', 'esDescuento', 'esPorcentaje'];
  importeSeleccionado: Importes;

  // Variables per mostrar / amagar contingut del template
  editarImporte = false;
  crearImporte = false;

  // Mode de visualització per defecte
  metodoVisualizacion = 'all';

  tiposDescuentos: Importes[] = [];

  @ViewChild('jugadoresAsignadosSeleccionados') jugadoresAsignadosSeleccionados: MatSelectionList;
  @ViewChild('entrenadoresAsignadosSeleccionados') entrenadoresAsignadosSeleccionados: MatSelectionList;
  @ViewChild('directivosAsignadosSeleccionados') directivosAsignadosSeleccionados: MatSelectionList;
  @ViewChild('familiaresAsignadosSeleccionados') familiaresAsignadosSeleccionados: MatSelectionList;
  @ViewChild('jugadoresDisponiblesSeleccionados') jugadoresDisponiblesSeleccionados: MatSelectionList;
  @ViewChild('entrenadoresDisponiblesSeleccionados') entrenadoresDisponiblesSeleccionados: MatSelectionList;
  @ViewChild('directivosDisponiblesSeleccionados') directivosDisponiblesSeleccionados: MatSelectionList;
  @ViewChild('familiaresDisponiblesSeleccionados') familiaresDisponiblesSeleccionados: MatSelectionList;
  @ViewChild('jugadoresDisponiblesJovenesSeleccionados') jugadoresDisponiblesJovenesSeleccionados: MatSelectionList;

  @ViewChild('jugadoresDisponiblesFilter') jugadoresDisponiblesFilter: ElementRef;

  primerEntradorCount = 0;
  segundoEntrenadorCount = 0;
  delegadosCount = 0;

  // Variable on mostrarem l'error, en cas que n'hi hagi
  error: string;

  constructor(
    private temporadasService: TemporadasService,
    public dialog: MatDialog,
    public spinner: NgxSpinnerService) { }

  ngOnInit() {
    this.temporadasService.getTemporadas().subscribe((res) => {
      this.temporadas = res;
      this.temporadaActual = this.temporadas[0]['temporada'];
    });
    this.temporadasService.getEquipos().subscribe( (res) => this.equipos = res);
    this.temporadasService.getImportes(this.metodoVisualizacion).subscribe(res => {
      this.dataSource.data = res as Importes[];
    });
    this.temporadasService.getTiposDescuentos().subscribe((res) => this.tiposDescuentos = res);
  }

  volverImportes(){
    this.importeSeleccionado = null;
    this.crearImporte = false;
    setTimeout(() => {
      this.ngOnInit();
    }, 100);
  }

  public startImporteAdd() {
    this.error = '';
    this.importeSeleccionado = new Importes();
    this.crearImporte = true;
  }

  public startEdit = (Importes: Importes) => {
    this.error = '';
    this.importeSeleccionado = Importes;
    this.editarImporte = true;
  }

  public editar() {
    this.editarImporte = true;
  }

  saveImporte(){
    this.error = '';
    /*this.spinner.show();
    this.temporadasService.update(this.importeSeleccionado)
      .subscribe(
        (res) => {
          this.volverImportes();
          this.spinner.hide();
        },
        (err) => {
          this.spinner.hide();
          this.error = err;
        }
      );*/
  }

  addTemporada() {
    let dialogRef = this.dialog.open(ConfirmDialogComponent, {
      disableClose: false,
      width: '400px'
    });
    dialogRef.componentInstance.confirmMessage = 'Si creas una nueva temporada se desactivará la temporada actual';

    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        const dialogRef2 = this.dialog.open(CrearTemporadaComponent, {
          disableClose: true
        });
        dialogRef2.afterClosed().subscribe( result2 => {
          if (result2) {
            this.ngOnInit();
          }
        });
      }
      dialogRef = null;
    });
  }

  addEquipo() {
    const dialogRef = this.dialog.open(CrearEquipoComponent, {
      disableClose: false
    });
    dialogRef.afterClosed().subscribe( () => {
      this.ngOnInit();
    });
  }

  editEquipo(equipo: Equipo) {
    const dialogRef = this.dialog.open(CrearEquipoComponent, {
      disableClose: false,
      data: {equipo},
    });
    dialogRef.afterClosed().subscribe( () => {
      this.ngOnInit();
    });
  }

  verJugadores(equipo: Equipo, filtro: string) {
    this.asignarJugadores = true;
    this.equipoSeleccionado = equipo;
    this.temporadasService.getJugadores(equipo, filtro).subscribe((res) => {
      // console.log(res);
      this.jugadoresAsignados = res['jugadoresAsignados'];
      this.jugadoresDisponibles = res['jugadoresDisponibles'];
      this.jugadoresDisponiblesJovenes = res['jugadoresDisponiblesJovenes'];
    });
  }

  verEntrenadores(equipo: Equipo, filtro: string) {
    this.primerEntradorCount = 0;
    this.segundoEntrenadorCount = 0;
    this.asignarEntrenadores = true;
    this.equipoSeleccionado = equipo;
    this.temporadasService.getEntrenadores(equipo, filtro).subscribe((res) => {
      this.entrenadoresAsignados = res['entrenadoresAsignados'];
      this.entrenadoresDisponibles = res['entrenadoresDisponibles'];
      this.entrenadoresAsignados.forEach((entrenador) => {
        switch (Number(entrenador.id_tipo_entrenador)) {
          case 1:
            this.primerEntradorCount++;
            break;
          case 2:
            this.segundoEntrenadorCount++;
            break;
        }
      });
    });
  }

  verDelegados(equipo: Equipo, filtro) {
    this.delegadosCount = 0;
    this.asignarDelegados = true;
    this.equipoSeleccionado = equipo;
    this.temporadasService.getDelegados(equipo, filtro).subscribe((res) => {
      this.directivosAsignados = res['directivosAsignados'];
      this.familiaresAsignados = res['familiaresAsignados'];
      this.directivosDisponibles = res['directivosDisponibles'];
      this.familiaresDisponibles = res['familiaresDisponibles'];
      this.delegadosCount = res['directivosAsignados'].length + res['familiaresAsignados'].length;
    });
  }

  volver() {
    this.asignarJugadores = false;
    this.asignarEntrenadores = false;
    this.asignarDelegados = false;
    this.ngOnInit();
  }

  anyadirTodos() {
    const dialogRef = this.dialog.open(ConfirmDialogComponent, {
      disableClose: false,
      width: '400px'
    });
    dialogRef.componentInstance.confirmMessage = '¿Añadir todos los jugadores disponibles entre los años '
      + this.equipoSeleccionado.nacidos_desde_anyo + ' y ' + this.equipoSeleccionado.nacidos_hasta_anyo + '?';

    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.spinner.show();
        this.temporadasService.anyadirAEquipo(this.jugadoresDisponibles, this.equipoSeleccionado).subscribe(
          () => {
            this.verJugadores(this.equipoSeleccionado, '');
            this.spinner.hide();
          },
          (err) => {
            this.spinner.hide();
            let dialogRef2 = this.dialog.open(ErrorDialogComponent, {
              disableClose: false,
              width: '400px'
            });
            dialogRef2.componentInstance.errorMessage = err;
            dialogRef2.afterClosed().subscribe(() => dialogRef2 = null);
        });
      }
    });
  }

  anyadirSeleccionados() {
    this.spinner.show();
    const jugadoresDisponiblesSeleccion: Jugador[] = [];
    this.jugadoresDisponiblesSeleccionados.selectedOptions.selected.map(j => jugadoresDisponiblesSeleccion.push(j.value));
    this.jugadoresDisponiblesJovenesSeleccionados.selectedOptions.selected.map(j => jugadoresDisponiblesSeleccion.push(j.value));
    this.temporadasService.anyadirAEquipo(jugadoresDisponiblesSeleccion, this.equipoSeleccionado).subscribe(
      () => {
        this.verJugadores(this.equipoSeleccionado, '');
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
      });
  }

  eliminarSeleccionados() {
    this.spinner.show();
    const jugadoresAsignadosSeleccion: Jugador[] = [];
    this.jugadoresAsignadosSeleccionados.selectedOptions.selected.map(j => jugadoresAsignadosSeleccion.push(j.value));
    this.temporadasService.eliminarDeEquipo(jugadoresAsignadosSeleccion, this.equipoSeleccionado).subscribe(
      () => {
        this.verJugadores(this.equipoSeleccionado, '');
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
      });
  }

  eliminarTodos() {
    const dialogRef = this.dialog.open(ConfirmDialogComponent, {
      disableClose: false,
      width: '400px'
    });
    dialogRef.componentInstance.confirmMessage = '¿Eliminar todos los jugadores actualmente asignados?';

    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.spinner.show();
        this.temporadasService.eliminarDeEquipo(this.jugadoresAsignados, this.equipoSeleccionado).subscribe(
          () => {
            this.verJugadores(this.equipoSeleccionado, '');
            this.spinner.hide();
          },
          (err) => {
            this.spinner.hide();
            let dialogRef2 = this.dialog.open(ErrorDialogComponent, {
              disableClose: false,
              width: '400px'
            });
            dialogRef2.componentInstance.errorMessage = err;
            dialogRef2.afterClosed().subscribe(() => dialogRef2 = null);
        });
      }
    });
  }

  editarJugador(jugador: Jugador) {
    this.dialog.open(EditarJugadorDialogComponent, {
      data: {jugador},
      minHeight: '700px',
      disableClose: true
    });
  }

  editarEntrenador(entrenador: Entrenador) {
    const dialogRef = this.dialog.open(EditarEntrenadorDialogComponent, {
      data: {entrenador},
      minHeight: '700px',
      disableClose: true
    });
    dialogRef.afterClosed().subscribe(
      () => {
        if (this.asignarEntrenadores){
          this.verEntrenadores(this.equipoSeleccionado, '');
        }
        if (this.asignarDelegados){
          this.verDelegados(this.equipoSeleccionado, '');
        }
      }
    );
  }

  editarFamiliar(familiar: Familiar) {
    const dialogRef = this.dialog.open(EditarFamiliarDialogComponent, {
      data: {familiar},
      minHeight: '700px',
      disableClose: true
    });
    dialogRef.afterClosed().subscribe(
      () => {
        if (this.asignarEntrenadores){
          this.verEntrenadores(this.equipoSeleccionado, '');
        }
        if (this.asignarDelegados){
          this.verDelegados(this.equipoSeleccionado, '');
        }
      }
    );
  }

  editarDirectivo(directivo: Directivo) {
    const dialogRef = this.dialog.open(EditarDirectivoDialogComponent, {
      data: {directivo},
      minHeight: '700px',
      disableClose: true
    });
    dialogRef.afterClosed().subscribe(
      () => {
        if (this.asignarEntrenadores){
          this.verEntrenadores(this.equipoSeleccionado, '');
        }
        if (this.asignarDelegados){
          this.verDelegados(this.equipoSeleccionado, '');
        }
      }
    );
  }

  editarDorsales() {
    const dialogRef = this.dialog.open(EditarDorsalesComponent, {
      data: {equipo: this.equipoSeleccionado},
      minWidth: '700px',
      maxHeight: '700px',
      disableClose: true
    });
    dialogRef.afterClosed().subscribe(() => {
      if (this.jugadoresDisponiblesFilter !== undefined) {
        this.verJugadores(this.equipoSeleccionado, this.jugadoresDisponiblesFilter.nativeElement.value);
      } else if (this.asignarJugadores === true) {
        this.verJugadores(this.equipoSeleccionado, '');
      }
    });
  }

  anyadirEntrenador(idTipoEntrenador: number) {
    const entrenadoresSeleccionadosCount = this.entrenadoresDisponiblesSeleccionados.selectedOptions.selected.length;
    if (entrenadoresSeleccionadosCount > 1 && idTipoEntrenador === 1) {
      let dialogRef = this.dialog.open(ErrorDialogComponent, {
        disableClose: false,
        width: '400px'
      });
      dialogRef.componentInstance.errorMessage = 'No se puede añadir más de 1 primer entrenador';
      dialogRef.afterClosed().subscribe(() => dialogRef = null);
    } else if (entrenadoresSeleccionadosCount + this.segundoEntrenadorCount > 3 && idTipoEntrenador === 2) {
      let dialogRef = this.dialog.open(ErrorDialogComponent, {
        disableClose: false,
        width: '400px'
      });
      dialogRef.componentInstance.errorMessage = 'No se puede añadir más de 3 segundos entrenadores';
      dialogRef.afterClosed().subscribe(() => dialogRef = null);
    } else {
      this.spinner.show();
      const entrenadoresDisponiblesSeleccion: Entrenador[] = [];
      this.entrenadoresDisponiblesSeleccionados.selectedOptions.selected.map(j => entrenadoresDisponiblesSeleccion.push(j.value));
      this.temporadasService.anyadirEntrenador(entrenadoresDisponiblesSeleccion, idTipoEntrenador, this.equipoSeleccionado)
        .subscribe(
          () => {
            this.verEntrenadores(this.equipoSeleccionado, '');
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
          });
    }
  }
  eliminarEntrenadoresSeleccionados() {
    this.spinner.show();
    const entrenadoresAsignadosSeleccion: Entrenador[] = [];
    this.entrenadoresAsignadosSeleccionados.selectedOptions.selected.map(j => entrenadoresAsignadosSeleccion.push(j.value));
    this.temporadasService.eliminarEntrenador(entrenadoresAsignadosSeleccion, this.equipoSeleccionado)
      .subscribe(
      () => {
        this.verEntrenadores(this.equipoSeleccionado, '');
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
      });
  }
  eliminarTodosEntrenadores() {
    const dialogRef = this.dialog.open(ConfirmDialogComponent, {
      disableClose: false,
      width: '400px'
    });
    dialogRef.componentInstance.confirmMessage = '¿Eliminar todos los entrenadores actualmente asignados?';

    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.spinner.show();
        this.temporadasService.eliminarEntrenador(this.entrenadoresAsignados, this.equipoSeleccionado).subscribe(
          () => {
            this.verEntrenadores(this.equipoSeleccionado, '');
            this.spinner.hide();
          },
          (err) => {
            this.spinner.hide();
            let dialogRef2 = this.dialog.open(ErrorDialogComponent, {
              disableClose: false,
              width: '400px'
            });
            dialogRef2.componentInstance.errorMessage = err;
            dialogRef2.afterClosed().subscribe(() => dialogRef2 = null);
          });
      }
    });
  }

  anyadirDelegado() {
    let delegadosSeleccionadosCount = this.directivosDisponiblesSeleccionados.selectedOptions.selected.length;
    delegadosSeleccionadosCount += this.familiaresDisponiblesSeleccionados.selectedOptions.selected.length;
    if (this.delegadosCount + delegadosSeleccionadosCount > 3) {
      let dialogRef = this.dialog.open(ErrorDialogComponent, {
        disableClose: false,
        width: '400px'
      });
      dialogRef.componentInstance.errorMessage = 'No se puede asignar más de 3 delegados por equipo';
      dialogRef.afterClosed().subscribe(() => dialogRef = null);
    } else {
      this.spinner.show();
      const directivosDisponiblesSeleccion: Array<number> = [];
      const familiaresDisponiblesSeleccion: Array<number> = [];
      this.directivosDisponiblesSeleccionados.selectedOptions.selected.map(j => directivosDisponiblesSeleccion.push(j.value.id));
      this.familiaresDisponiblesSeleccionados.selectedOptions.selected.map(j => familiaresDisponiblesSeleccion.push(j.value.id));
      this.temporadasService.anyadirDelegado(directivosDisponiblesSeleccion, this.equipoSeleccionado)
        .subscribe(
          () => {
            this.temporadasService.anyadirDelegado(familiaresDisponiblesSeleccion, this.equipoSeleccionado)
              .subscribe(
                () => {
                  this.verDelegados(this.equipoSeleccionado, '');
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
                });
          },
          (err) => {
            this.spinner.hide();
            let dialogRef = this.dialog.open(ErrorDialogComponent, {
              disableClose: false,
              width: '400px'
            });
            dialogRef.componentInstance.errorMessage = err;
            dialogRef.afterClosed().subscribe(() => dialogRef = null);
          });
    }
  }

  eliminarDelegadosSeleccionados() {
    this.spinner.show();
    const delegadosAsignadosSeleccion: Array<number> = [];
    this.directivosAsignadosSeleccionados.selectedOptions.selected.map(j => delegadosAsignadosSeleccion.push(j.value.id));
    this.familiaresAsignadosSeleccionados.selectedOptions.selected.map(j => delegadosAsignadosSeleccion.push(j.value.id));
    this.temporadasService.eliminarDelegado(delegadosAsignadosSeleccion, this.equipoSeleccionado)
      .subscribe(
        () => {
          this.verDelegados(this.equipoSeleccionado, '');
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
        });
  }
  eliminarTodosDelegados() {
    const dialogRef = this.dialog.open(ConfirmDialogComponent, {
      disableClose: false,
      width: '400px'
    });
    dialogRef.componentInstance.confirmMessage = '¿Eliminar todos los delegados actualmente asignados?';

    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.spinner.show();
        const delegadosAsignadosSeleccion: Array<number> = [];
        this.directivosAsignados.map(j => delegadosAsignadosSeleccion.push(j.id));
        this.familiaresAsignados.map(j => delegadosAsignadosSeleccion.push(j.id));
        this.temporadasService.eliminarDelegado(delegadosAsignadosSeleccion, this.equipoSeleccionado).subscribe(
          () => {
            this.verDelegados(this.equipoSeleccionado, '');
            this.spinner.hide();
          },
          (err) => {
            this.spinner.hide();
            let dialogRef2 = this.dialog.open(ErrorDialogComponent, {
              disableClose: false,
              width: '400px'
            });
            dialogRef2.componentInstance.errorMessage = err;
            dialogRef2.afterClosed().subscribe(() => dialogRef2 = null);
          });
      }
    });
  }

  switchActivoWeb(equipo: Equipo) {
    this.spinner.show();
    this.temporadasService.switchActivoWeb(equipo).subscribe(
      () => {
        this.spinner.hide();
        this.ngOnInit();
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

  downloadEquipos() {
    //this.temporadasService.getListadoEquipos().subscribe( (res) => top.location.href = res['resultat']);
    this.temporadasService.getListadoEquipos().subscribe((res) => {
      //console.log(res['resultat']);
      top.location.href = res['resultat'];
    });
  }
  downloadPartidos() {
    this.temporadasService.getListadoPartidos().subscribe((res) => {
      top.location.href = res['resultat'];
    });
  }
}
