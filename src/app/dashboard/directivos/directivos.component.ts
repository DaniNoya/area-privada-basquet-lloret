import {AfterViewInit, Component, ElementRef, Input, OnInit, ViewChild} from '@angular/core';
import {MatDialog, MatPaginator, MatSort, MatTableDataSource} from '@angular/material';
import {DirectivosService} from './directivos.service';
import {Directivo} from '../../classes/directivo';
import {Jugador} from '../../classes/jugador';
import {TiposParentesco} from '../../classes/tipos_parentesco';
import {FamiliaresService} from '../familiares/familiares.service';
import {ErrorDialogComponent} from '../dialogs/error-dialog/error-dialog.component';
import {Cargo} from '../../classes/cargo';
import {JugadoresService} from '../jugadores/jugadores.service';
import {EntrenadoresService} from '../entrenadores/entrenadores.service';
import {Sexo} from '../../classes/sexo';
import {GlobalService} from '../global.service';
import {BajaDialogComponent} from '../dialogs/baja-dialog/baja-dialog.component';
import {formatDate} from '@angular/common';
import { NgxSpinnerService } from 'ngx-spinner';

@Component({
  selector: 'app-directivos',
  templateUrl: './directivos.component.html',
  styleUrls: ['./directivos.component.css']
})
export class DirectivosComponent implements OnInit, AfterViewInit {

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

  dataSource = new MatTableDataSource<Directivo>();
  displayedColumns: string[] = ['nombre', 'primer_apellido', 'segundo_apellido', 'cargo', 'actions'];

  // Array de Càrrecs
  cargos: Cargo[] = [];

  // Array de sexes
  sexos: Sexo[] = [];

  // Array de parentescos per mostrar en el modal a l'hora de seleccionar un nou familiar
  tiposParentesco: TiposParentesco[] = [];
  parentescoSeleccionado: number;

  // Objecte entrenador, que servirà de pivot per tractar les dades
  directivoSeleccionado: Directivo;

  // Variables per mostrar / amagar contingut del template
  editarDirectivo = false;
  crearDirectivo = false;

  // Mode de visualització per defecte
  metodoVisualizacion = 'alta';

  // Variable on mostrarem l'error, en cas que n'hi hagi
  error: string;

  // Serveis necessaris per aquest component
  constructor(private directivosService: DirectivosService,
              public dialog: MatDialog,
              private familiaresService: FamiliaresService,
              private jugadoresService: JugadoresService,
              private entrenadoresService: EntrenadoresService,
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
      switch (this.addToString) {
        case 'familiar':
          this.familiaresService.getTipos().subscribe(items => this.tiposParentesco = items);
          break;
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
        case 'familiar':
          this.familiaresService.getAll('', false).subscribe(
            (familiares) => {
              familiares.forEach((v, k) => {
                exclusionList += v.id + ',';
              });
            }
          );
          break;
      }
      setTimeout(() => {
        let exclusion = exclusionList.slice(0, -1);
        exclusion += ')';
        this.directivosService.getDirectivos(this.metodoVisualizacion, exclusion).subscribe(res => {
          setTimeout(() => {
            this.dataSource.data = res as Directivo[];
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
      this.directivosService.getDirectivos(this.metodoVisualizacion, exclusionList).subscribe(res => {
        setTimeout(() => {
          this.dataSource.data = res as Directivo[];
          this.isLoadingResults = false;
        }, 500);
      });
    } else {
      // Obtenim el llistat de directius
      this.directivosService.getDirectivos(this.metodoVisualizacion).subscribe(res => {
          this.dataSource.data = res as Directivo[];
          this.isLoadingResults = false;
      });
    }
    // Format de Sorting, canviem els valors a minuscules per ordenar
    this.dataSource.sortingDataAccessor = (item, property) => {
      return item[property].toString().toLowerCase();
    };
    // Columnes on aplicarà el filtre
    this.dataSource.filterPredicate = (data, filter: string): boolean => {
      return data.nombre.toLowerCase().includes(filter) || data.primer_apellido.toLowerCase().includes(filter) ||
        data.segundo_apellido.toLowerCase().includes(filter) || data.cargo.toLowerCase().includes(filter);
    };
    // Subscripció als carrecs
    this.directivosService.getCargos().subscribe((res) => this.cargos = res);
    // Obtenim els sexes
    this.globalService.getSexos().subscribe((res) => this.sexos = res);
  }

  ngAfterViewInit() {
    // Assignem els elements del template (sorti i paginator) al component d'ordenar i paginar del datasource
    this.dataSource.sort = this.sort;
    this.dataSource.paginator = this.paginator;
  }

  public applyFilter = (value: string) => {
    this.dataSource.filter = value.trim().toLocaleLowerCase();
  }

  volver() {
    this.directivoSeleccionado = null;
    this.crearDirectivo = false;
    setTimeout(() => {
      this.ngOnInit();
      this.ngAfterViewInit();
      this.applyFilter('');
    }, 100);
  }

  public showItem = (directivo: Directivo) => {
    this.error = '';
    this.directivoSeleccionado = directivo;
    this.editarDirectivo = false;
  }

  public editar() {
    this.editarDirectivo = true;
  }

  public save() {
    this.error = '';
    this.spinner.show();
    this.directivosService.update(this.directivoSeleccionado)
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

    this.directivosService.store(this.directivoSeleccionado)
      .subscribe(
        (res) => {
          this.volver();
        },
        (err) => this.error = err
      );
  }

  public startAdd() {
    this.error = '';
    this.directivoSeleccionado = new Directivo();
    this.crearDirectivo = true;
  }

  public startEdit = (directivo: Directivo) => {
    this.error = '';
    this.directivoSeleccionado = directivo;
    this.editarDirectivo = true;
  }

  public bajaItem = (directivo: Directivo) => {
    let dialogRef = this.dialog.open(BajaDialogComponent, {
      disableClose: true,
      width: '400px'
    });
    dialogRef.componentInstance.jugador = directivo.nombre + ' ' + directivo.primer_apellido + ' ' + directivo.segundo_apellido;

    dialogRef.afterClosed().subscribe(result => {
      if (!!result) {
        this.error = '';
        directivo.baja = '1';
        directivo.fecha_baja = formatDate(result, 'yyyy-MM-dd', 'en-US');
        this.directivosService.update(directivo)
          .subscribe(
            () => {
              this.directivoSeleccionado = null;
              this.ngOnInit();
              this.ngAfterViewInit();
            },
            (err) => this.error = err
          );
      }
      dialogRef = null;
    });
  }

  public altaItem = (directivo: Directivo) => {
    this.error = '';
    directivo.baja = '0';
    this.directivosService.update(directivo)
      .subscribe(
        (res) => {
          this.directivoSeleccionado = null;
          this.ngOnInit();
          this.ngAfterViewInit();
        },
        (err) => this.error = err
      );
  }

  addDirectivoToFamiliar(directivo: Directivo) {
    if (this.parentescoSeleccionado !== undefined) {
      this.familiaresService.setFamiliar(directivo.id, this.addTo.id, this.parentescoSeleccionado).subscribe(
        (res) => {
          this.modalObert.ngOnInit();
          this.modalObert.ngAfterViewInit();
          this.ngOnInit();
          this.ngAfterViewInit();
          this.modalObert.anyadirFamiliar = false;
        },
        (err) => {
          console.log(err);
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

  assigna(directivo: Directivo) {
    switch (this.assignaA) {
      case 'jugador':
        this.jugadoresService.assign(directivo.id).subscribe(
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
        this.entrenadoresService.assign(directivo.id).subscribe(
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
      case 'familiar':
        this.directivosService.assign(directivo.id).subscribe(
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
