import { AfterViewInit, Component, ElementRef, Input, OnInit, ViewChild } from '@angular/core';
import { MatDialog, MatPaginator, MatSort, MatTableDataSource } from '@angular/material';
import { Partido } from '../../classes/partido';
import { Equipo } from '../../classes/equipo';
import { PartidosService } from './partidos.service';

import { GlobalService } from '../global.service';
import { NgxSpinnerService } from 'ngx-spinner';

@Component({
  selector: 'app-partidos',
  templateUrl: './partidos.component.html',
  styleUrls: ['./partidos.component.css']
})
export class PartidosComponent implements OnInit,AfterViewInit {

  isLoadingResults = true;

  // Lectura d'objectes del template
  @ViewChild('paginatorPartidosTemporada') paginatorPartidosTemporada: MatPaginator;
  @ViewChild('paginatorPartidosAmistosos') paginatorPartidosAmistosos: MatPaginator;
  @ViewChild('sortPartidosTemporada') sortPartidosTemporada: MatSort;
  @ViewChild('sortPartidosAmistosos') sortPartidosAmistosos: MatSort;
  @ViewChild('filterPartidosTemporada') filterPartidosTemporada: ElementRef;
  @ViewChild('filterPartidosAmistosos') filterPartidosAmistosos: ElementRef;

  // Objecte que emmagatzema les dades per la taula
  dataSourcePartidosTemporada = new MatTableDataSource<Partido>();
  dataSourcePartidosAmistosos = new MatTableDataSource<Partido>();
  // Columnes que mostrarem a la taula
  displayedColumns: string[] = ['nombreEquipoLocal', 'nombre_equipo_visitante', 'fecha_partido', 'hora_partido', 'actions'];

  // Objecte partido, que servirà de pivot per tractar les dades
  partidoSeleccionado: Partido;

  equipos: Equipo[] = [];

  // Variables per mostrar / amagar contingut del template
  editarPartidoTemporada = false;
  editarPartidoAmistoso = false;
  crearPartido = false;

  // Variable on mostrarem l'error, en cas que n'hi hagi
  error: string;

  constructor(private partidosService: PartidosService,
              private globalService: GlobalService,
              public dialog: MatDialog,
              private spinner: NgxSpinnerService) { }

  ngOnInit() {
    this.isLoadingResults = true;
    this.partidosService.getPartidos().subscribe(res => {
      this.dataSourcePartidosTemporada.data = res['partidosTemporada'];
      this.dataSourcePartidosAmistosos.data = res['partidosAmistosos'];
      this.isLoadingResults = false;
    });
    this.partidosService.getEquipos().subscribe((res) => this.equipos = res);
    // Columnes on aplicarà el filtre Partidos Temporada
    this.dataSourcePartidosTemporada.filterPredicate = (data, filter: string): boolean => {
      const strABuscar = filter.toLowerCase();
      return data.nombreEquipoLocal.toLowerCase().includes(strABuscar) || data.nombre_equipo_visitante.toLowerCase().startsWith(strABuscar)
        || data.fecha_partido.toLowerCase().startsWith(strABuscar);
    };
    // Columnes on aplicarà el filtre Partidos Amistosos
    this.dataSourcePartidosAmistosos.filterPredicate = (data, filter: string): boolean => {
      const strABuscar = filter.toLowerCase();
      return data.nombreEquipoLocal.toLowerCase().includes(strABuscar) || data.nombre_equipo_visitante.toLowerCase().startsWith(strABuscar)
        || data.fecha_partido.toLowerCase().startsWith(strABuscar);
    };
  }

  ngAfterViewInit() {
    // Assignem els elements del template (sort i paginator) al component d'ordenar i paginar del datasource
    this.dataSourcePartidosTemporada.sort = this.sortPartidosTemporada;
    this.dataSourcePartidosTemporada.paginator = this.paginatorPartidosTemporada;
    this.dataSourcePartidosAmistosos.sort = this.sortPartidosAmistosos;
    this.dataSourcePartidosAmistosos.paginator = this.paginatorPartidosAmistosos;
  }

  public applyFilterPartidosTemporada = (value: string) => {
    this.dataSourcePartidosTemporada.filter = value.trim().toLocaleLowerCase();
  }
  public applyFilterPartidosAmistosos = (value: string) => {
    this.dataSourcePartidosAmistosos.filter = value.trim().toLocaleLowerCase();
  }

  volver() {
    this.partidoSeleccionado = null;
    this.crearPartido = false;
    this.editarPartidoTemporada = false;
    this.editarPartidoAmistoso = false;
    setTimeout(() => {
      this.ngOnInit();
      this.ngAfterViewInit();
      this.applyFilterPartidosTemporada('');
      this.applyFilterPartidosAmistosos('');
    }, 100);
  }

  public editar() {
    this.editarPartidoTemporada = true;
    this.editarPartidoAmistoso = true;
  }

  public startAdd() {
    this.error = '';
    this.partidoSeleccionado = new Partido;
    this.crearPartido = true;
  }

  public startEdit = (partido: Partido) => {
    this.error = '';
    this.partidoSeleccionado = partido;
    this.editarPartidoTemporada = true;
    this.editarPartidoAmistoso = true;
  }

  public showItem = (partido: Partido) => {
    this.error = '';
    this.partidoSeleccionado = partido;
    //console.log(this.partidoSeleccionado);
    this.editarPartidoTemporada = false;
    this.editarPartidoAmistoso = false;
  }

  public delete = (partido: Partido) => {
    this.error = '';
    this.spinner.show();
    partido.borrado = 1;
    this.partidosService.update(partido)
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

  public save() {
    this.error = '';
    this.spinner.show();
    this.partidosService.update(this.partidoSeleccionado)
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
    this.spinner.show();
    this.partidosService.store(this.partidoSeleccionado)
      .subscribe(
        (res) => {
          this.volver();
          this.spinner.hide();
        },
        (err) => {
          this.spinner.hide();
          this.error = err
        }
      );
  }
}
