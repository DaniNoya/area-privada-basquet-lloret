import {AfterViewInit, Component, ElementRef, Input, OnInit, ViewChild} from '@angular/core';
import {MatDialog, MatPaginator, MatSort, MatTableDataSource} from '@angular/material';
import {JugadoresService} from './jugadores.service';
import {Jugador} from '../../classes/jugador';
import {FamiliaresDialogComponent} from '../dialogs/familiares-dialog/familiares-dialog.component';
import {PagosUsuarioDialogComponent} from '../dialogs/pagos-usuario-dialog/pagos-usuario-dialog.component';
import {FamiliaresService} from '../familiares/familiares.service';
import {TiposParentesco} from '../../classes/tipos_parentesco';
import {EntrenadoresService} from '../entrenadores/entrenadores.service';
import {DirectivosService} from '../directivos/directivos.service';
import {ErrorDialogComponent} from '../dialogs/error-dialog/error-dialog.component';
import {Sexo} from '../../classes/sexo';
import {GlobalService} from '../global.service';
import {BajaDialogComponent} from '../dialogs/baja-dialog/baja-dialog.component';
import {formatDate} from '@angular/common';
import { NgxSpinnerService } from 'ngx-spinner';
import {ImageResult, ResizeOptions} from 'ng2-imageupload';
import {TipoPago} from '../../classes/tipo_pago';
import {Pago} from "../../classes/pago";
import {MatSelect} from "@angular/material/select";

@Component({
  selector: 'app-jugadores',
  templateUrl: './jugadores.component.html',
  styleUrls: ['./jugadores.component.css']
})
export class JugadoresComponent implements OnInit, AfterViewInit {

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

  @ViewChild('selectConcepto') selectConcepto: MatSelect;

  // Objecte que emmagatzema les dades per la taula
  dataSource = new MatTableDataSource<Jugador>();
  // Columnes que mostrarem a la taula
  displayedColumns: string[] = ['nombre', 'primer_apellido', 'segundo_apellido', 'fecha_nacimiento', 'sexo', 'observaciones', 'actions'];

  // Array de parentescos per mostrar en el modal a l'hora de seleccionar un nou familiar
  tiposParentesco: TiposParentesco[] = [];
  parentescoSeleccionado: number;

  // Objecte jugador, que servirà de pivot per tractar les dades
  jugadorSeleccionado: Jugador;

  // Variables per mostrar / amagar contingut del template
  editarJugador = false;
  crearJugador = false;

  // Mode de visualització per defecte
  metodoVisualizacion = 'alta';

  // Mode de visualització per mostrar els que no tenen familiars assignats
  sinFamiliaresAsignados = false;

  // Mode de visualització per mostrar els que no estan assignats a cap equip
  sinEquiposAsignados = false;

  // Variable on mostrarem l'error, en cas que n'hi hagi
  error: string;

  // Array de sexes
  sexos: Sexo[] = [];

  // Array de tipo pago
  tiposPago: TipoPago[] = [];
  conceptoSelecciopnado: number;

  estadoJugador = 'inscritos';

  // Mides màximes per redimensionar fotos
  resizeOptions: ResizeOptions = {
    resizeMaxHeight: 300,
    resizeMaxWidth: 300
  };

  // Serveis necessaris per aquest component
  constructor(private jugadoresService: JugadoresService,
              private entrenadoresService: EntrenadoresService,
              private directivosService: DirectivosService,
              public dialog: MatDialog,
              private familiaresService: FamiliaresService,
              private globalService: GlobalService,
              private spinner: NgxSpinnerService) {}

  ngOnInit() {
    // Mostrem l'spinner de carga
    this.isLoadingResults = true;
    // Si es modal no mostrem la columna "observaciones"0
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
        case 'entrenador':
          this.entrenadoresService.getEntrenadores(this.metodoVisualizacion).subscribe(
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
        this.jugadoresService.getJugadores(this.metodoVisualizacion, this.sinFamiliaresAsignados, this.sinEquiposAsignados, this.conceptoSelecciopnado, this.estadoJugador, exclusion).subscribe(res => {
          setTimeout(() => {
            this.dataSource.data = res as Jugador[];
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
      this.jugadoresService.getJugadores(this.metodoVisualizacion, this.sinFamiliaresAsignados, this.sinEquiposAsignados, this.conceptoSelecciopnado, this.estadoJugador, exclusionList).subscribe(res => {
        setTimeout(() => {
          this.dataSource.data = res as Jugador[];
          this.isLoadingResults = false;
        }, 500);
      });
    } else {
      // Obtenim el llistat de jugadors
      this.jugadoresService.getJugadores(this.metodoVisualizacion, this.sinFamiliaresAsignados, this.sinEquiposAsignados, this.conceptoSelecciopnado, this.estadoJugador).subscribe(res => {
        this.dataSource.data = res as Jugador[];
        this.isLoadingResults = false;
      });
    }
    // Obtenim els sexes
    this.globalService.getSexos().subscribe((res) => this.sexos = res);
    // Obtenim los tipos de pago
    /*console.log(this.selectConcepto);*/
    this.globalService.getTiposPago().subscribe((res) => {
      this.tiposPago = res;
      if (!this.conceptoSelecciopnado && this.tiposPago) {
        this.conceptoSelecciopnado = this.tiposPago[0].id;
        this.ngOnInit();
      }
    });
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
    // Assignem els elements del template (sort i paginator) al component d'ordenar i paginar del datasource
    this.dataSource.sort = this.sort;
    this.dataSource.paginator = this.paginator;
  }

  volver() {
    this.jugadorSeleccionado = null;
    this.crearJugador = false;
    setTimeout(() => {
    this.ngOnInit();
    this.ngAfterViewInit();
    this.applyFilter('');
    }, 100);
  }

  public applyFilter = (value: string) => {
    this.dataSource.filter = value.trim().toLocaleLowerCase();
  }

  public showItem = (jugador: Jugador) => {
    this.error = '';
    this.jugadorSeleccionado = jugador;
    const year = new Date().getFullYear();
    const month = new Date().getMonth();
    const day = new Date().getDate();
    const today = new Date(year, month, day, 0, 0, 0, 0).getTime();
    const birthday = new Date(this.jugadorSeleccionado.fecha_nacimiento);
    birthday.setHours(0, 0, 0, 0);
    this.jugadorSeleccionado.edad = Math.floor((today - birthday.getTime()) / 1000 / 3600 / 24 / 365);
    this.editarJugador = false;
  }

  public editar() {
    this.editarJugador = true;
  }

  public save() {
    this.error = '';
    this.spinner.show();
    this.jugadoresService.update(this.jugadorSeleccionado)
        .subscribe(
          () => {
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

    this.jugadoresService.store(this.jugadorSeleccionado)
      .subscribe(
        (res) => {
          // Quan emmagatzemem el jugador, fem que mostri el detall per si li volem afegir familiars
          this.crearJugador = false;
          this.editarJugador = false;
          this.jugadorSeleccionado.id = res['idJugador'];
        },
        (err) => this.error = err
      );
  }

  public startAdd() {
    this.error = '';
    this.jugadorSeleccionado = new Jugador();
    this.crearJugador = true;
  }

  public startEdit = (jugador: Jugador) => {
    this.error = '';
    this.jugadorSeleccionado = jugador;
    const year = new Date().getFullYear();
    const month = new Date().getMonth();
    const day = new Date().getDate();
    const today = new Date(year, month, day, 0, 0, 0, 0).getTime();
    const birthday = new Date(this.jugadorSeleccionado.fecha_nacimiento);
    birthday.setHours(0, 0, 0, 0);
    this.jugadorSeleccionado.edad = Math.floor((today - birthday.getTime()) / 1000 / 3600 / 24 / 365);
    this.editarJugador = true;
  }

  public bajaItem = (jugador: Jugador) => {
    let dialogRef = this.dialog.open(BajaDialogComponent, {
      disableClose: true,
      width: '400px'
    });
    dialogRef.componentInstance.jugador = jugador.nombre + ' ' + jugador.primer_apellido + ' ' + jugador.segundo_apellido;

    dialogRef.afterClosed().subscribe(result => {
      if (!!result) {
        this.error = '';
        jugador.baja = '1';
        jugador.fecha_baja = formatDate(result, 'yyyy-MM-dd', 'en-US');
        this.jugadoresService.update(jugador)
          .subscribe(
            () => {
              this.jugadorSeleccionado = null;
              this.ngOnInit();
              this.ngAfterViewInit();
            },
            (err) => this.error = err
          );
      }
      dialogRef = null;
    });
  }

  public altaItem = (jugador: Jugador) => {
    this.error = '';
    jugador.baja = '0';
    this.jugadoresService.update(jugador)
      .subscribe(
        () => {
          this.jugadorSeleccionado = null;
          this.ngOnInit();
          this.ngAfterViewInit();
        },
        (err) => this.error = err
      );
  }

  startFamiliares() {
    this.dialog.open(FamiliaresDialogComponent, {
      data: {jugador: this.jugadorSeleccionado },
      width: '900px',
      minHeight: '700px',
      maxHeight: '900px',
      disableClose: true
    });
  }

  startPagosUsuario() {
    let modalConta = this.dialog.open(PagosUsuarioDialogComponent, {
      data: {jugador: this.jugadorSeleccionado },
      width: '900px',
      minHeight: '700px',
      maxHeight: '900px',
      disableClose: true
    });

    modalConta.afterClosed().subscribe((res) => {
      if(res.quotes){
        this.jugadorSeleccionado.quotes = res.quotes;
      }
    });
  }

  selected(imageResult: ImageResult) {
    this.jugadorSeleccionado.foto = imageResult.resized
      && imageResult.resized.dataURL
      || imageResult.dataURL;
    this.file.nativeElement.value = '';
  }

  addJugadorToFamiliar(jugador: Jugador) {
    if (this.parentescoSeleccionado !== undefined) {
      this.familiaresService.setFamiliar(jugador.id, this.addTo.id, this.parentescoSeleccionado).subscribe(
        () => {
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
          dialogRef.afterClosed().subscribe(() => dialogRef = null);
        }
      );
    }
  }
  assigna(jugador: Jugador) {
    switch (this.assignaA) {
      case 'entrenador':
        this.entrenadoresService.assign(jugador.id).subscribe(
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
        this.familiaresService.assign(jugador.id).subscribe(
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
        this.directivosService.assign(jugador.id).subscribe(
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

  getPagosFiltered(arr: Pago[], idTipoPago: number): Pago[] {
    return arr.filter(p => p.tipoPago == idTipoPago);
  }

  getRestante(jugador: Jugador, idTipoPago: number): Pago[] {
    const ret: Pago[] = [];
    const quota = jugador.quotes.find(q => q.idTipo == idTipoPago);
    if (quota.quota - quota.pagado > 0) {
      const p = new Pago();
      p.importe = quota.quota - quota.pagado;
      ret.push(p);
      return ret;
    }
  }

  checkHavePagos(arr: Pago[], idTipoPago: number): boolean {
    return arr.some(p => p.tipoPago == idTipoPago);
  }

  checkHaveInscripcion(jugador: Jugador, idTipoPago: number): boolean {
    return jugador.quotes.some(q => q.idTipo == idTipoPago);
  }

  downloadJugadores() {
    this.jugadoresService.getListadoJugadores().subscribe((res) => {
      top.location.href = res['resultat'];
    });
  }
}
