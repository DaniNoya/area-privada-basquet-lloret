import {AfterViewInit, Component, ElementRef, Input, OnInit, ViewChild} from '@angular/core';
import {MatDialog, MatPaginator, MatSort, MatTableDataSource} from '@angular/material';
import {Entrenador} from '../../classes/entrenador';
import {EntrenadoresService} from './entrenadores.service';
import {Jugador} from '../../classes/jugador';
import {TiposParentesco} from '../../classes/tipos_parentesco';
import {FamiliaresService} from '../familiares/familiares.service';
import {ErrorDialogComponent} from '../dialogs/error-dialog/error-dialog.component';
import {NivelFormacion} from '../../classes/nivel_formacion';
import {JugadoresService} from '../jugadores/jugadores.service';
import {DirectivosService} from '../directivos/directivos.service';
import {Sexo} from '../../classes/sexo';
import {GlobalService} from '../global.service';
import {BajaDialogComponent} from '../dialogs/baja-dialog/baja-dialog.component';
import {formatDate} from '@angular/common';
import { NgxSpinnerService } from 'ngx-spinner';
import {ImageResult, ResizeOptions} from 'ng2-imageupload';

@Component({
  selector: 'app-entrenadores',
  templateUrl: './entrenadores.component.html',
  styleUrls: ['./entrenadores.component.css']
})
export class EntrenadoresComponent implements OnInit, AfterViewInit {

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
  @ViewChild('file') file: ElementRef;

  // Objecte que emmagatzema les dades per la taula
  dataSource = new MatTableDataSource<Entrenador>();
  displayedColumns: string[] = ['nombre', 'primer_apellido', 'segundo_apellido', 'nivel_formacion', 'actions'];

  // Array de nivells de formació
  nivelesFormacion: NivelFormacion[] = [];

  // Array de sexes
  sexos: Sexo[] = [];

  // Array de parentescos per mostrar en el modal a l'hora de seleccionar un nou familiar
  tiposParentesco: TiposParentesco[] = [];
  parentescoSeleccionado: number;

  // Objecte entrenador, que servirà de pivot per tractar les dades
  entrenadorSeleccionado: Entrenador;

  // Variables per mostrar / amagar contingut del template
  editarEntrenador = false;
  crearEntrenador = false;

  // Mode de visualització per defecte
  metodoVisualizacion = 'alta';

  // Variable on mostrarem l'error, en cas que n'hi hagi
  error: string;

  // Mides màximes per redimensionar fotos
  resizeOptions: ResizeOptions = {
    resizeMaxHeight: 300,
    resizeMaxWidth: 300
  };

  // Serveis necessaris per aquest component
  constructor(private entrenadoresService: EntrenadoresService,
              public dialog: MatDialog,
              private familiaresService: FamiliaresService,
              private jugadoresService: JugadoresService,
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
        case 'familiar':
          this.familiaresService.getAll('', false).subscribe(
            (familiares) => {
              familiares.forEach((v, k) => {
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
        this.entrenadoresService.getEntrenadores(this.metodoVisualizacion, exclusion).subscribe(res => {
          setTimeout(() => {
            this.dataSource.data = res as Entrenador[];
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
      this.entrenadoresService.getEntrenadores(this.metodoVisualizacion, exclusionList).subscribe(res => {
        setTimeout(() => {
          this.dataSource.data = res as Entrenador[];
          this.isLoadingResults = false;
        }, 500);
      });
    } else {
      // Obtenim el llistat d'entrenadors
      this.entrenadoresService.getEntrenadores(this.metodoVisualizacion).subscribe(res => {
          this.dataSource.data = res as Entrenador[];
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
        data.segundo_apellido.toLowerCase().includes(filter);
    };
    // Subscripció als nivells de formació
    this.entrenadoresService.getNiveles().subscribe((res) => this.nivelesFormacion = res);
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
    this.entrenadorSeleccionado = null;
    this.crearEntrenador = false;
    setTimeout(() => {
      this.ngOnInit();
      this.ngAfterViewInit();
      this.applyFilter('');
    }, 100);
  }

  public showItem = (entrenador: Entrenador) => {
    this.error = '';
    this.entrenadorSeleccionado = entrenador;
    this.editarEntrenador = false;
  }

  public editar() {
    this.editarEntrenador = true;
  }

  public save() {
    this.error = '';
    this.spinner.show();
    this.entrenadoresService.update(this.entrenadorSeleccionado)
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

    this.entrenadoresService.store(this.entrenadorSeleccionado)
      .subscribe(
        (res) => {
          this.volver();
        },
        (err) => this.error = err
      );
  }

  public startAdd() {
    this.error = '';
    this.entrenadorSeleccionado = new Entrenador();
    this.crearEntrenador = true;
  }

  public startEdit = (entrenador: Entrenador) => {
    this.error = '';
    this.entrenadorSeleccionado = entrenador;
    this.editarEntrenador = true;
  }

  public bajaItem = (entrenador: Entrenador) => {
    let dialogRef = this.dialog.open(BajaDialogComponent, {
      disableClose: true,
      width: '400px'
    });
    dialogRef.componentInstance.jugador = entrenador.nombre + ' ' + entrenador.primer_apellido + ' ' + entrenador.segundo_apellido;

    dialogRef.afterClosed().subscribe(result => {
      if (!!result) {
        this.error = '';
        entrenador.baja = '1';
        entrenador.fecha_baja = formatDate(result, 'yyyy-MM-dd', 'en-US');
        this.entrenadoresService.update(entrenador)
          .subscribe(
            () => {
              this.entrenadorSeleccionado = null;
              this.ngOnInit();
              this.ngAfterViewInit();
            },
            (err) => this.error = err
          );
      }
      dialogRef = null;
    });
  }

  public altaItem = (entrenador: Entrenador) => {
    this.error = '';
    entrenador.baja = '0';
    this.entrenadoresService.update(entrenador)
      .subscribe(
        (res) => {
          this.entrenadorSeleccionado = null;
          this.ngOnInit();
          this.ngAfterViewInit();
        },
        (err) => this.error = err
      );
  }

  addEntrenadorToFamiliar(entrenador: Entrenador) {
    if (this.parentescoSeleccionado !== undefined) {
      this.familiaresService.setFamiliar(entrenador.id, this.addTo.id, this.parentescoSeleccionado).subscribe(
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

  assigna(entrenador: Entrenador) {
    switch (this.assignaA) {
      case 'jugador':
        this.jugadoresService.assign(entrenador.id).subscribe(
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
        this.familiaresService.assign(entrenador.id).subscribe(
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
        this.directivosService.assign(entrenador.id).subscribe(
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

  selected(imageResult: ImageResult) {
    this.entrenadorSeleccionado.foto = imageResult.resized
      && imageResult.resized.dataURL
      || imageResult.dataURL;
    this.file.nativeElement.value = '';
  }
}
