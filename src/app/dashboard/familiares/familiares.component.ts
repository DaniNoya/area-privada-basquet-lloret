import {AfterViewInit, Component, ElementRef, Input, OnInit, ViewChild} from '@angular/core';
import {Jugador} from '../../classes/jugador';
import {MatDialog, MatPaginator, MatSort, MatTableDataSource} from '@angular/material';
import {FamiliaresService} from './familiares.service';
import {Familiar} from '../../classes/familiar';
import {TiposParentesco} from '../../classes/tipos_parentesco';
import {ErrorDialogComponent} from '../dialogs/error-dialog/error-dialog.component';
import {JugadoresService} from '../jugadores/jugadores.service';
import {EntrenadoresService} from '../entrenadores/entrenadores.service';
import {DirectivosService} from '../directivos/directivos.service';
import {Sexo} from '../../classes/sexo';
import {GlobalService} from '../global.service';
import {BajaDialogComponent} from '../dialogs/baja-dialog/baja-dialog.component';
import {formatDate} from '@angular/common';
import { NgxSpinnerService } from 'ngx-spinner';

@Component({
  selector: 'app-familiares',
  templateUrl: './familiares.component.html',
  styleUrls: ['./familiares.component.css']
})
export class FamiliaresComponent implements OnInit, AfterViewInit {

  isLoadingResults = true;

  // Inputs per controlar si carrega en un modal
  @Input() modal = false;
  @Input() addTo: Jugador;
  @Input() addToString: string;
  @Input() modalObert;

  // Inputs per controlar si volem afegir una persona ja creada
  @Input() assignarPersona = false;
  @Input() assignaA;
  @Input() controlador;

  // Lectura d'objectes del template
  @ViewChild(MatPaginator) paginator: MatPaginator;
  @ViewChild(MatSort) sort: MatSort;
  @ViewChild('filtrar') input: ElementRef;

  // Objecte que emmagatzema les dades per la taula
  dataSource = new MatTableDataSource<Familiar>();
  // Columnes que mostrarem a la taula
  displayedColumns: string[] = ['nombre', 'primer_apellido', 'segundo_apellido', 'fecha_nacimiento', 'observaciones', 'actions'];

  // Array de parentescos per mostrar en el modal a l'hora de seleccionar un nou familiar
  tiposParentesco: TiposParentesco[] = [];
  parentescoSeleccionado: number;

  // Objecte jugador, que servirà de pivot per tractar les dades
  familiarSeleccionado: Familiar;

  // Array de sexes
  sexos: Sexo[] = [];

  // Variables per mostrar / amagar contingut del template
  editarFamiliar = false;
  crearFamiliar = false;

  // Mode de visualització per defecte
  metodoVisualizacion = 'alta';

  // Mode de visualització per mostrar els que no tenen familiars assignats
  sinJugadoresAsignados = false;

  // Variable on mostrarem l'error, en cas que n'hi hagi
  error: string;

  // Serveis necessaris per aquest component
  constructor(private familiaresService: FamiliaresService,
              public dialog: MatDialog,
              private jugadoresService: JugadoresService,
              private entrenadoresService: EntrenadoresService,
              private directivosService: DirectivosService,
              private globalService: GlobalService,
              private spinner: NgxSpinnerService) { }

  ngOnInit() {
    // Mostrem l'spinner de carga
    this.isLoadingResults = true;
    // Si es modal no mostrem la columna "observaciones"
    if (this.modal) {
      const index = this.displayedColumns.indexOf('observaciones', 0);
      if (index > -1) {
        this.displayedColumns.splice(index, 1);
      }
      // Depenent de la variables rebuda obtenim les dades relacionades necessaries
      if (this.addToString === 'familiar') {
          this.familiaresService.getTipos().subscribe(items => this.tiposParentesco = items);
      }
    }
    // Si estem en el mode d'assignar, eliminem les persones repetides
    if (this.assignarPersona) {
      let exclusionList = '(';
      switch (this.assignaA) {
        case 'jugador':
          this.jugadoresService.getJugadores('', false, false).subscribe(
            (jugadores) => {
              jugadores.forEach((v, k) => {
                exclusionList += v.id + ',';
              });
            }
          );
          break;
        case 'entrenador':
          this.entrenadoresService.getEntrenadores('').subscribe(
            (entrenadores) => {
              entrenadores.forEach((v, k) => {
                exclusionList += v.id + ',';
              });
            }
          );
          break;
        case 'directivo':
          this.directivosService.getDirectivos('').subscribe(
            (directivos) => {
              directivos.forEach((v, k) => {
                exclusionList += v.id + ',';
              });
            }
          );
          break;
      }
      setTimeout(() => {
        let exclusion = exclusionList.slice(0, -1);
        exclusion += ')';
        this.familiaresService.getAll(this.metodoVisualizacion, this.sinJugadoresAsignados, exclusion).subscribe(res => {
          setTimeout(() => {
            this.dataSource.data = res as Familiar[];
            this.isLoadingResults = false;
          }, 500);
        });
      }, 1000);
    } else if (this.modal) { // Si es modal eliminem el seu propi element i els familiars ja assignats
      let exclusionList = '(';
      this.modalObert.familiaresAssignados.forEach((v, k) => {
        exclusionList += v.id + ',';
      });
      exclusionList += this.addTo.id + ')';
      this.familiaresService.getAll(this.metodoVisualizacion, this.sinJugadoresAsignados, exclusionList).subscribe(res => {
        setTimeout(() => {
          this.dataSource.data = res as Familiar[];
          this.isLoadingResults = false;
        }, 500);
      });
    } else {
      // Obtenim el llistat de familiars
      this.familiaresService.getAll(this.metodoVisualizacion, this.sinJugadoresAsignados).subscribe(res => {
          this.dataSource.data = res as Familiar[];
          this.isLoadingResults = false;
      });
    }
    // Obtenim els sexes
    this.globalService.getSexos().subscribe((res) => this.sexos = res);
    // Format de Sorting, canviem els valors a minuscules per ordenar
    this.dataSource.sortingDataAccessor = (item, property) => {
      return item[property].toString().toLowerCase();
    };
    // Columnes on aplicarà el filtre
    this.dataSource.filterPredicate = (data, filter: string): boolean => {
      return data.nombre.toLowerCase().includes(filter) || data.primer_apellido.toLowerCase().includes(filter) ||
        data.segundo_apellido.toLowerCase().includes(filter) || data.fecha_nacimiento.toLowerCase().includes(filter);
    };
  }

  ngAfterViewInit() {
    // Assignem els elements del template (sorti i paginator) al component d'ordenar i paginar del datasource
    this.dataSource.sort = this.sort;
    this.dataSource.paginator = this.paginator;
  }

  volver() {
    this.familiarSeleccionado = null;
    this.crearFamiliar = false;
    setTimeout(() => {
      this.ngOnInit();
      this.ngAfterViewInit();
      this.applyFilter('');
    }, 100);
  }

  public applyFilter = (value: string) => {
    this.dataSource.filter = value.trim().toLocaleLowerCase();
  }

  public showItem = (familiar: Familiar) => {
    this.error = '';
    this.familiarSeleccionado = familiar;
    this.editarFamiliar = false;
  }

  public editar() {
    this.editarFamiliar = true;
  }

  public save() {
    this.error = '';
    this.spinner.show();
    this.familiaresService.update(this.familiarSeleccionado)
      .subscribe(
        (res) => {
          this.volver();
          this.spinner.hide();
        },
        (err) => {
          this.spinner.hide();
          this.error = err;
        }
      );
  }

  public store() {
    this.error = '';

    this.familiaresService.store(this.familiarSeleccionado)
      .subscribe(
        () => {
          this.modalObert.ngOnInit();
          this.volver();
        },
        (err) => this.error = err
      );
  }

  public startAdd() {
    this.error = '';
    this.familiarSeleccionado = new Familiar();
    this.crearFamiliar = true;
  }

  public startEdit = (familiar: Familiar) => {
    this.error = '';
    this.familiarSeleccionado = familiar;
    this.editarFamiliar = true;
  }

  public bajaItem = (familiar: Familiar) => {
    let dialogRef = this.dialog.open(BajaDialogComponent, {
      disableClose: true,
      width: '400px'
    });
    dialogRef.componentInstance.jugador = familiar.nombre + ' ' + familiar.primer_apellido + ' ' + familiar.segundo_apellido;

    dialogRef.afterClosed().subscribe(result => {
      if (!!result) {
        this.error = '';
        familiar.baja = '1';
        familiar.fecha_baja = formatDate(result, 'yyyy-MM-dd', 'en-US');
        this.familiaresService.update(familiar)
          .subscribe(
            () => {
              this.familiarSeleccionado = null;
              this.ngOnInit();
              this.ngAfterViewInit();
            },
            (err) => this.error = err
          );
      }
      dialogRef = null;
    });
  }

  public altaItem = (familiar: Familiar) => {
    this.error = '';
    familiar.baja = '0';
    this.familiaresService.update(familiar)
      .subscribe(
        (res) => {
          this.familiarSeleccionado = null;
          this.ngOnInit();
          this.ngAfterViewInit();
        },
        (err) => this.error = err
      );
  }

  addFamiliarToFamiliar(familiar: Familiar) {
    if (this.parentescoSeleccionado !== undefined) {
      this.familiaresService.setFamiliar(familiar.id, this.addTo.id, this.parentescoSeleccionado).subscribe(
        () => {
          this.modalObert.ngOnInit();
          this.modalObert.ngAfterViewInit();
          this.ngOnInit();
          this.ngAfterViewInit();
          this.modalObert.anyadirFamiliar = false;
        },
        (err) => {
          let dialogRef = this.dialog.open(ErrorDialogComponent, {
            disableClose: false,
            width: '400px'
          });
          dialogRef.componentInstance.errorMessage = err;
          dialogRef.afterClosed().subscribe(res => dialogRef = null);
        }
      );
    }
  }

  assigna(familiar: Familiar) {
    switch (this.assignaA) {
      case 'jugador':
        this.jugadoresService.assign(familiar.id).subscribe(
          () => {
            this.controlador.volver();
          },
          (err) => {
            let dialogRef = this.dialog.open(ErrorDialogComponent, {
              disableClose: false,
              width: '400px'
            });
            dialogRef.componentInstance.errorMessage = err;
            dialogRef.afterClosed().subscribe(res => dialogRef = null);
          }
        );
        break;
      case 'entrenador':
        this.entrenadoresService.assign(familiar.id).subscribe(
          () => {
            this.controlador.volver();
          },
          (err) => {
            let dialogRef = this.dialog.open(ErrorDialogComponent, {
              disableClose: false,
              width: '400px'
            });
            dialogRef.componentInstance.errorMessage = err;
            dialogRef.afterClosed().subscribe(res => dialogRef = null);
          }
        );
        break;
      case 'directivo':
        this.directivosService.assign(familiar.id).subscribe(
          () => {
            this.controlador.volver();
          },
          (err) => {
            let dialogRef = this.dialog.open(ErrorDialogComponent, {
              disableClose: false,
              width: '400px'
            });
            dialogRef.componentInstance.errorMessage = err;
            dialogRef.afterClosed().subscribe(res => dialogRef = null);
          }
        );
        break;
    }
  }
}
