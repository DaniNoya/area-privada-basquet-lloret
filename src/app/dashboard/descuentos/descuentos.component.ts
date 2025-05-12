import {AfterViewInit, Component, ElementRef, Input, OnInit, ViewChild} from '@angular/core';
import {MatDialog, MatPaginator, MatSort, MatTableDataSource} from '@angular/material';
import {Descuento} from '../../classes/Descuento';
import {DescuentosService} from './descuentos.service';
import {Jugador} from '../../classes/jugador';
import {JugadoresService} from '../jugadores/jugadores.service';
import {FamiliaresService} from '../familiares/familiares.service';
import {Temporada} from '../../classes/temporada';
import {TemporadasService} from '../temporadas/temporadas.service';
import {TiposParentesco} from '../../classes/tipos_parentesco';
import {TipoPago} from '../../classes/tipo_pago';
import {Importes} from '../../classes/importes';
import {ErrorDialogComponent} from '../dialogs/error-dialog/error-dialog.component';
import {GlobalService} from '../global.service';
import {BajaDialogComponent} from '../dialogs/baja-dialog/baja-dialog.component';
import {formatDate} from '@angular/common';
import { NgxSpinnerService } from 'ngx-spinner';

@Component({
  selector: 'app-descuentos',
  templateUrl: './descuentos.component.html',
  styleUrls: ['./descuentos.component.css']
})
export class DescuentosComponent implements OnInit {

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
  dataSource = new MatTableDataSource<Descuento>();
  // Columnes que mostrarem a la taula
  displayedColumns: string[] = ['concepto', 'dni', 'porcentaje', 'desAnioPasado', 'actions'];

  // Array de parentescos per mostrar en el modal a l'hora de seleccionar un nou familiar
  tiposParentesco: TiposParentesco[] = [];
  parentescoSeleccionado: number;

  temporadas: Temporada[] = [];
  temporadaActual: string;
  
  conceptoDescuentos: TipoPago[] = [];

  tiposDescuentos: Importes[] = [];

  // Objecte descuento, que servirà de pivot per tractar les dades
  descuentoSeleccionado: Descuento;

  // Variables per mostrar / amagar contingut del template
  editarDescuento = false;
  crearDescuento = false;

  // Mode de visualització per defecte
  metodoVisualizacion = 'temporadaRegular';

  // Variable on mostrarem l'error, en cas que n'hi hagi
  error: string;

  // Serveis necessaris per aquest component
  constructor(private descuentosService: DescuentosService,
              private jugadoresService: JugadoresService,
              private familiaresService: FamiliaresService,
              private temporadasService: TemporadasService,
              private globalService: GlobalService,
              public dialog: MatDialog,
              private spinner: NgxSpinnerService) { }

  ngOnInit() {
    // Mostrem l'spinner de carga
    this.isLoadingResults = true;
    if (this.modal) { // Si es modal eliminem el seu propi element i els familiars ja assignats
      let exclusionList = '(';
      this.modalObert.familiaresAssignados.forEach((v, k) => {
        exclusionList += v.id + ',';
      });
      exclusionList += this.addTo.id + ')';
      this.descuentosService.getDescuentos(this.metodoVisualizacion, exclusionList).subscribe(res => {
        setTimeout(() => {
          this.dataSource.data = res as Descuento[];
          this.isLoadingResults = false;
        }, 500);
      });
    } else {
      // Obtenim el llistat de directius
      this.descuentosService.getDescuentos(this.metodoVisualizacion).subscribe(res => {
          this.dataSource.data = res as Descuento[];
          this.isLoadingResults = false;
      });
    }
    this.temporadasService.getTemporadas().subscribe((res) => this.temporadas = res)
    this.descuentosService.getConceptoDescuentos().subscribe((res) => this.conceptoDescuentos = res)
    this.descuentosService.getTiposDescuentos().subscribe((res) => this.tiposDescuentos = res)
    // Format de Sorting, canviem els valors a minuscules per ordenar
    this.dataSource.sortingDataAccessor = (item, property) => {
      return item[property].toString().toLowerCase();
    };
    // Columnes on aplicarà el filtre
    this.dataSource.filterPredicate = (data, filter: string): boolean => {
      return data.dni.toLowerCase().includes(filter) || data.concepto.toLowerCase().includes(filter);
    };
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
    this.descuentoSeleccionado = null;
    this.crearDescuento = false;
    setTimeout(() => {
      this.ngOnInit();
      this.ngAfterViewInit();
      this.applyFilter('');
    }, 100);
  }

  public showItem = (descuento: Descuento) => {
    this.error = '';
    this.descuentoSeleccionado = descuento;
    this.editarDescuento = false;
  }

  public editar() {
    this.editarDescuento = true;
  }

  public save() {
    this.error = '';
    this.spinner.show();
    this.descuentosService.update(this.descuentoSeleccionado)
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

    this.descuentosService.store(this.descuentoSeleccionado)
      .subscribe(
        (res) => {
          this.volver();
        },
        (err) => this.error = err
      );
  }

  public delete = (descuento: Descuento) => {
    this.error = '';
    this.spinner.show();
    descuento.borrado = 1;
    this.descuentosService.update(descuento)
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

  public startAdd() {
    this.error = '';
    this.descuentoSeleccionado = new Descuento();
    this.crearDescuento = true;
  }

  public startEdit = (descuento: Descuento) => {
    this.error = '';
    this.descuentoSeleccionado = descuento;
    this.editarDescuento = true;
  }
}
