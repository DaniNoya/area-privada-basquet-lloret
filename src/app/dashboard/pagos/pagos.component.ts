import {AfterViewInit, Component, ElementRef, Input, OnInit, ViewChild} from '@angular/core';
import {MatDialog, MatPaginator, MatSort, MatTableDataSource} from '@angular/material';
import {Pago} from '../../classes/pago';
import {PagosService} from './pagos.service';
import {Jugador} from '../../classes/jugador';
import {JugadoresService} from '../jugadores/jugadores.service';
import {Temporada} from '../../classes/temporada';
import {TemporadasService} from '../temporadas/temporadas.service';
import {TipoPago} from '../../classes/tipo_pago';

import {GlobalService} from '../global.service';
import {formatDate} from '@angular/common';
import { NgxSpinnerService } from 'ngx-spinner';
import {FormControl} from '@angular/forms';
import {Observable} from 'rxjs';
import {map, startWith} from 'rxjs/operators';
import {Familiar} from '../../classes/familiar';
import {ErrorDialogComponent} from '../dialogs/error-dialog/error-dialog.component';
import {ConfirmDialogComponent} from '../dialogs/confirm-dialog/confirm-dialog.component';
import {MatInput} from "@angular/material/input";

export class JugadorPagos extends Jugador {
  familiar: Familiar;
  importeRestante: number;
  importeRestanteDespuesPago: number;
}

export class PagoModulo extends Pago {
  importePago: number;
  importeOriginal: number;
}

@Component({
  selector: 'app-pagos',
  templateUrl: './pagos.component.html',
  styleUrls: ['./pagos.component.css']
})
export class PagosComponent implements OnInit, AfterViewInit {

  isLoadingResults = true;

  // Lectura d'objectes del template
  @ViewChild('paginator') paginator: MatPaginator;
  @ViewChild('paginatorNoCompletados') paginatorNoCompletados: MatPaginator;
  @ViewChild('paginatorFallidos') paginatorFallidos: MatPaginator;
  @ViewChild('sort') sort: MatSort;
  @ViewChild('sortNoCompletados') sortNoCompletados: MatSort;
  @ViewChild('sortFallidos') sortFallidos: MatSort;
  @ViewChild('filter') filter: ElementRef;
  @ViewChild('filterNoCompletados') filterNoCompletados: ElementRef;
  @ViewChild('filterFallido') filterFallido: ElementRef;
  @ViewChild('inputImporte') inputImporte: FormControl;
  @ViewChild('inputAddImporte') inputAddImporte: FormControl;

  // Objecte que emmagatzema les dades per la taula
  dataSource = new MatTableDataSource<PagoModulo>();
  dataSourceNoCompletados = new MatTableDataSource<PagoModulo>();
  dataSourceFallidos = new MatTableDataSource<PagoModulo>();
  // Columnes que mostrarem a la taula
  displayedColumns: string[] = ['tutor', 'jugador', 'fechaTransaccion', 'tipoPagoDescripcion', 'descripcion', 'importe', 'pagoManual', 'actions'];
  displayedColumnsFallidos: string[] = ['dniTutor', 'dniJugador', 'fechaTransaccion', 'tipoPagoDescripcion', 'descripcion', 'importe', 'actions'];

  // Objecte pago, que servirà de pivot per tractar les dades
  pagoSeleccionado: PagoModulo;
  jugadorSeleccionado: JugadorPagos = new JugadorPagos();

  temporadas: Temporada[] = [];
  temporadaActual: string;

  // Variables per mostrar / amagar contingut del template
  editarPago = false;
  editarPagoManual = false;
  crearPago = false;

  // Mode de visualització per defecte
  metodoVisualizacion = 'all';
  tipoMovimiento = 'all';

  // Variable on mostrarem l'error, en cas que n'hi hagi
  error: string;

  // Array de tipo pago
  tiposPago: TipoPago[] = [];

  dniJugadorControl = new FormControl();
  jugadores: JugadorPagos[] = [];
  filteredJugadores: Observable<JugadorPagos[]>;

  constructor(private pagosService: PagosService,
              private jugadoresService: JugadoresService,
              private temporadasService: TemporadasService,
              private globalService: GlobalService,
              public dialog: MatDialog,
              private spinner: NgxSpinnerService) { }

  ngOnInit() {
    // Mostrem l'spinner de carga
    this.isLoadingResults = true;
    this.pagosService.getPagos(this.metodoVisualizacion, this.tipoMovimiento).subscribe(res => {
      this.dataSource.data = res['pagos'];
      this.dataSourceNoCompletados.data = res['pagosNoCompletados'];
      this.dataSourceFallidos.data = res['pagosFallidos'];
      this.isLoadingResults = false;
    });
    // Obtenim los tipos de pago
    this.globalService.getTiposPago().subscribe((res) => this.tiposPago = res);
    // Format de Sorting, canviem els valors a minuscules per ordenar
    this.dataSource.sortingDataAccessor = (item, property) => {
      return item[property].toString().toLowerCase();
    };
    // Columnes on aplicarà el filtre
    /*this.dataSource.filterPredicate = (data, filter: string): boolean => {
      const strABuscar = filter.toLowerCase();
      if (data.tutor) {
        return data.tutor.toLowerCase().includes(strABuscar) || data.jugador.toLowerCase().includes(strABuscar)
          || data.dniTutor.toLowerCase().startsWith(strABuscar) || data.dniJugador.toLowerCase().startsWith(strABuscar);
      } else {
        return data.jugador.toLowerCase().includes(strABuscar) || data.dniJugador.toLowerCase().startsWith(strABuscar);
      }
    };*/
    // Autocomplete de DNIs
    this.pagosService.getDNIs().subscribe((res) => {
      this.jugadores = res as JugadorPagos[];
    });
    this.filteredJugadores = this.dniJugadorControl.valueChanges
      .pipe(
        startWith(''),
        map(value => this.filterJugadores(value))
      );
  }

  ngAfterViewInit() {
    // Assignem els elements del template (sort i paginator) al component d'ordenar i paginar del datasource
    this.dataSource.sort = this.sort;
    this.dataSource.paginator = this.paginator;
    this.dataSourceNoCompletados.sort = this.sortNoCompletados;
    this.dataSourceNoCompletados.paginator = this.paginatorNoCompletados;
    this.dataSourceFallidos.sort = this.sortFallidos;
    this.dataSourceFallidos.paginator = this.paginatorFallidos;
  }

  public applyFilter = (value: string) => {
    this.dataSource.filter = value.trim().toLocaleLowerCase();
  }

  public applyFilterNoCompletados = (value: string) => {
    this.dataSourceNoCompletados.filter = value.trim().toLocaleLowerCase();
  }

  public applyFilterFallidos = (value: string) => {
    this.dataSourceFallidos.filter = value.trim().toLocaleLowerCase();
  }

  private filterJugadores(value: any): JugadorPagos[] {
    if (value.constructor === String) {
      const filterValue = value.toLowerCase();
      return this.jugadores.filter(option => option.dni.toLowerCase().startsWith(filterValue)
        || option.nombre.toLowerCase().includes(filterValue)
        || option.primer_apellido.toLowerCase().includes(filterValue)
        || option.segundo_apellido.toLowerCase().includes(filterValue));
    } else {
      return this.jugadores;
    }
  }

  volver() {
    this.pagoSeleccionado = null;
    this.crearPago = false;
    this.editarPago = false;
    setTimeout(() => {
      this.ngOnInit();
      this.ngAfterViewInit();
      this.applyFilter('');
    }, 100);
  }

  public showItem = (pago: PagoModulo) => {
    this.error = '';
    this.pagoSeleccionado = pago;
    this.pagoSeleccionado.fechaTransaccion = formatDate(new Date(this.pagoSeleccionado.fechaTransaccion), 'yyyy-MM-ddTHH:mm:ss', 'en-US', 'CET');
    if (this.pagoSeleccionado.pagoCompletado == 1) {
      this.jugadorSeleccionado = this.jugadores.find(j => j.dni === this.pagoSeleccionado.dniJugador);
      const importes = this.jugadorSeleccionado.quotes.find(q => q.idTipo === this.pagoSeleccionado.tipoPago);
      const importeRestante = importes.quota - importes.pagado;
      this.jugadorSeleccionado.quota = importes.quota;
      this.jugadorSeleccionado.importeRestante = importeRestante;
      this.jugadorSeleccionado.importeRestanteDespuesPago = importeRestante;
    }
    this.editarPago = false;
    this.editarPagoManual = false;
  }

  public editar() {
    this.editarPago = true;
    this.editarPagoManual = true;
  }

  public completar = (pago: PagoModulo) => {
    let dialogRef = this.dialog.open(ConfirmDialogComponent, {
      disableClose: false,
      width: '400px'
    });
    dialogRef.componentInstance.confirmMessage = '¿Estás seguro que quieres completar el pago?';
    dialogRef.afterClosed().subscribe((response) => {
      if (response) {
        this.pagoSeleccionado = pago;
        this.pagoSeleccionado.pagoCompletado = 1;
        this.pagosService.update(this.pagoSeleccionado)
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
      dialogRef = null;
    });
  }

  public save() {
    this.error = '';
    this.spinner.show();
    this.pagosService.update(this.pagoSeleccionado)
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
    this.pagosService.store(this.pagoSeleccionado)
      .subscribe(
        (res) => {
          this.volver();
        },
        (err) => this.error = err
      );
  }

  public startAdd() {
    this.error = '';
    this.pagoSeleccionado = new PagoModulo();
    this.pagoSeleccionado.importe = 50;
    this.pagoSeleccionado.importePago = 50;
    this.pagoSeleccionado.pagoCompletado = 1;
    this.pagoSeleccionado.pagoManual = 1;
    this.pagoSeleccionado.fechaTransaccion = formatDate(new Date(), 'yyyy-MM-ddTHH:mm:ss', 'en-US', 'CET');
    this.jugadorSeleccionado = new JugadorPagos();
    this.crearPago = true;
    this.dniJugadorControl.setValue('');
  }

  public startEdit = (pago: PagoModulo) => {
    this.error = '';
    this.pagoSeleccionado = pago;
    this.pagoSeleccionado.fechaTransaccion = formatDate(new Date(this.pagoSeleccionado.fechaTransaccion), 'yyyy-MM-ddTHH:mm:ss', 'en-US', 'CET');
    this.jugadorSeleccionado = this.jugadores.find(j => j.dni === this.pagoSeleccionado.dniJugador);
    this.dniJugadorControl.setValue(this.jugadorSeleccionado.dni + ' - ' + this.jugadorSeleccionado.nombre + ' '
      + this.jugadorSeleccionado.primer_apellido + ' ' + this.jugadorSeleccionado.segundo_apellido);
    const importes = this.jugadorSeleccionado.quotes.find(q => q.idTipo === this.pagoSeleccionado.tipoPago);
    const importeRestante = importes.quota - importes.pagado;
    this.jugadorSeleccionado.quota = importes.quota;
    this.jugadorSeleccionado.importeRestante = importeRestante;
    this.jugadorSeleccionado.importeRestanteDespuesPago = importeRestante;
    this.editarPago = true;
    this.editarPagoManual = true;
  }

  checkIntroducedImport(value: number) {
    const importes = this.jugadorSeleccionado.quotes.find(q => q.idTipo === this.pagoSeleccionado.tipoPago);
    if (!importes) {
      this.openDialogMessage('El jugador <b>' + this.jugadorSeleccionado.nombre + ' ' +
        this.jugadorSeleccionado.primer_apellido + '</b> no está inscrito en ' +
        this.tiposPago.find(tp => tp.id === this.pagoSeleccionado.tipoPago).concepto);
      this.pagoSeleccionado.tipoPago = null;
      return;
    }
    const importeRestante = importes.quota - importes.pagado;
    if (value < 1) {
      this.openDialogMessage('El importe mínimo es <b>1€</b>');
      this.pagoSeleccionado.importe = 1;
    }
    if (!this.editarPago) {
      if (value > importeRestante) {
        this.openDialogMessage('El importe máximo debe ser el restante <b>' + importeRestante + '€</b>');
        this.pagoSeleccionado.importe = importeRestante;
      } /*else if (importeRestante - this.pagoSeleccionado.importe < 50 && importeRestante - this.pagoSeleccionado.importe > 0) {
        this.openDialogMessage('Con la cantidad de <b>' + this.pagoSeleccionado.importe +
          '€</b> el siguiente pago sería menor al mínimo (50€)');
        this.pagoSeleccionado.importe = importeRestante;
      }*/
      this.jugadorSeleccionado.importeRestanteDespuesPago = importeRestante - this.pagoSeleccionado.importe;
    } else {
      const diff = this.pagoSeleccionado.importe - this.pagoSeleccionado.importePago;
      if ((this.jugadorSeleccionado.importeRestanteDespuesPago - diff) < 0) {
        this.openDialogMessage('Con la cantidad de <b>' + this.pagoSeleccionado.importe +
          '€</b> se sobrepasa el importe total de la cuota');
        this.pagoSeleccionado.importe -= (0 - (this.jugadorSeleccionado.importeRestanteDespuesPago - diff));
        this.jugadorSeleccionado.importeRestanteDespuesPago = 0;
      } else {
        this.jugadorSeleccionado.importeRestanteDespuesPago -= diff;
      }
      if (!this.pagoSeleccionado.importeOriginal) {
        this.pagoSeleccionado.importeOriginal = this.pagoSeleccionado.importePago;
      }
      this.pagoSeleccionado.importePago = this.pagoSeleccionado.importe;
    }
  }

  fillContentByDni(value: any) {
    const jugador = value.option.value as JugadorPagos;
    this.dniJugadorControl.setValue(jugador.dni + ' - ' + jugador.nombre + ' '
      + jugador.primer_apellido + ' ' + jugador.segundo_apellido);

    const importes = jugador.quotes.find(q => q.idTipo === this.pagoSeleccionado.tipoPago);
    if (!importes) {
      this.openDialogMessage('El jugador <b>' + jugador.nombre + ' ' +
        jugador.primer_apellido + '</b> no está inscrito en ' +
        this.tiposPago.find(tp => tp.id === this.pagoSeleccionado.tipoPago).concepto);
      this.dniJugadorControl.setValue('');
      return;
    }
    const importeRestante = importes.quota - importes.pagado;

    if (this.dataSourceNoCompletados.data.find(d => d.dniJugador === jugador.dni && d.tipoPago === this.pagoSeleccionado.tipoPago)) {
      let dialogRef = this.dialog.open(ErrorDialogComponent, {
        disableClose: false,
        width: '400px'
      });
      dialogRef.componentInstance.errorMessage = 'El Jugador <b>' + jugador.nombre + ' '
        + jugador.primer_apellido + '</b> tiene pagos no completados<br/>' +
        'Revise el apartado antes de crear uno de nuevo';
      dialogRef.afterClosed().subscribe(() => {
        this.dniJugadorControl.setValue('');
        dialogRef = null;
      });
    } else {
      if (importeRestante > 0) {
        this.jugadorSeleccionado = jugador;
        this.jugadorSeleccionado.quota = importes.quota;
        this.jugadorSeleccionado.importeRestante = importeRestante;
        if (this.jugadorSeleccionado.familiar) {
          this.pagoSeleccionado.dniTutor = this.jugadorSeleccionado.familiar.dni;
          this.pagoSeleccionado.tutor = this.jugadorSeleccionado.familiar.nombre + ' ' +
            this.jugadorSeleccionado.familiar.primer_apellido + ' ' + this.jugadorSeleccionado.familiar.segundo_apellido;
        }
        this.pagoSeleccionado.dniJugador = this.jugadorSeleccionado.dni;
        this.pagoSeleccionado.jugador = this.jugadorSeleccionado.nombre + ' ' + this.jugadorSeleccionado.primer_apellido +
          ' ' + this.jugadorSeleccionado.segundo_apellido;
        this.checkIntroducedImport(this.pagoSeleccionado.importe);
      } else {
        let dialogRef = this.dialog.open(ErrorDialogComponent, {
          disableClose: false,
          width: '400px'
        });
        dialogRef.componentInstance.errorMessage = 'El Jugador <b>' + jugador.nombre + ' '
          + jugador.primer_apellido + '</b> no tiene pagos pendientes';
        dialogRef.afterClosed().subscribe(() => {
          this.dniJugadorControl.setValue('');
          dialogRef = null;
        });
      }
    }
  }

  openDialogMessage(message: string) {
    const dialogRef = this.dialog.open(ErrorDialogComponent, {
      disableClose: false,
      width: '400px'
    }).componentInstance.errorMessage = message;
  }

  downloadPagos() {
    this.pagosService.getListadoPagos(this.tipoMovimiento).subscribe((res) => {
      top.location.href = res['resultat'];
    });
  }
}
