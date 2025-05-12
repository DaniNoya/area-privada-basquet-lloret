import { AfterViewInit, Component, ElementRef, Input, OnInit, ViewChild } from '@angular/core';
import { MatDialog, MatPaginator, MatSort, MatTableDataSource } from '@angular/material';
import { formatDate } from '@angular/common';
import { NgxSpinnerService } from 'ngx-spinner';
import { BajaDialogComponent } from '../dialogs/baja-dialog/baja-dialog.component';
import { GlobalService } from '../global.service';
import { SociosService } from './socios.service';
import { Socio } from '../../classes/socio';
import { Sexo } from '../../classes/sexo';

@Component({
  selector: 'app-socios',
  templateUrl: './socios.component.html',
  styleUrls: ['./socios.component.css']
})
export class SociosComponent implements OnInit, AfterViewInit {

  isLoadingResults = true;

  // Inputs per controlar si carrega en un modal
  @Input() modal = false;

  // Inputs per controlar si volem afegir una persona ja creada
  @Input() assignarPersona = false;
  @Input() assignaA;
  @Input() controlador;

  // Lectura d'objectes del template
  @ViewChild(MatPaginator) paginator: MatPaginator;
  @ViewChild(MatSort) sort: MatSort;
  @ViewChild('filtrar') input: ElementRef;

  dataSource = new MatTableDataSource<Socio>();
  displayedColumns: string[] = ['partner_code', 'nombre', 'primer_apellido', 'segundo_apellido', 'actions'];

  // Array de sexes
  sexos: Sexo[] = [];

  // Objecte socio, que servirà de pivot per tractar les dades
  socioSeleccionado: Socio;

  // Variables per mostrar / amagar contingut del template
  editarSocio = false;
  crearSocio = false;

  // Mode de visualització per defecte
  metodoVisualizacion = 'alta';

  // Variable on mostrarem l'error, en cas que n'hi hagi
  error: string;

  // Serveis necessaris per aquest component
  constructor(private sociosService: SociosService,
              public dialog: MatDialog,
              private globalService: GlobalService,
              private spinner: NgxSpinnerService) { }

  ngOnInit() {
    // Mostrem l'spinner de carga
    this.isLoadingResults = true;
    
    // Obtenim el llistat de socis
    this.sociosService.getSocios(this.metodoVisualizacion).subscribe(res => {
      this.dataSource.data = res as Socio[];
      this.isLoadingResults = false;
    });

    // Format de Sorting, canviem els valors a minuscules per ordenar
    this.dataSource.sortingDataAccessor = (item, property) => {
      return item[property].toString().toLowerCase();
    };

    // Columnes on aplicarà el filtre
    this.dataSource.filterPredicate = (data, filter: string): boolean => {
      return data.partner_code.toLowerCase().includes(filter) || data.nombre.toLowerCase().includes(filter) || data.primer_apellido.toLowerCase().includes(filter) ||
        data.segundo_apellido.toLowerCase().includes(filter) || data.dni.toLowerCase().includes(filter);
    };

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
    this.socioSeleccionado = null;
    this.crearSocio = false;
    setTimeout(() => {
      this.ngOnInit();
      this.ngAfterViewInit();
      this.applyFilter('');
    }, 100);
  }

  public showItem = (socio: Socio) => {
    this.error = '';
    this.socioSeleccionado = socio;
    this.editarSocio = false;
  }

  public editar() {
    this.editarSocio = true;
  }

  public startAdd() {
    this.error = '';
    this.socioSeleccionado = new Socio();
    this.crearSocio = true;
  }

  public startEdit = (socio: Socio) => {
    this.error = '';
    this.socioSeleccionado = socio;
    this.editarSocio = true;
  }

  public save() {
    this.error = '';
    this.spinner.show();
    this.sociosService.update(this.socioSeleccionado).subscribe(
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
    this.sociosService.store(this.socioSeleccionado).subscribe(
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

  public bajaItem = (socio: Socio) => {
    let dialogRef = this.dialog.open(BajaDialogComponent, {
      disableClose: true,
      width: '400px'
    });
    dialogRef.componentInstance.jugador = socio.nombre + ' ' + socio.primer_apellido + ' ' + socio.segundo_apellido;

    dialogRef.afterClosed().subscribe(result => {
      if (!!result) {
        this.error = '';
        socio.baja = '1';
        socio.fecha_baja = formatDate(result, 'yyyy-MM-dd', 'en-US');
        this.sociosService.update(socio).subscribe(
          () => {
            this.socioSeleccionado = null;
            this.ngOnInit();
            this.ngAfterViewInit();
          },
          (err) => this.error = err
        );
      }
      dialogRef = null;
    });
  }

  public altaItem = (socio: Socio) => {
    this.error = '';
    socio.baja = '0';
    this.sociosService.update(socio).subscribe(
      () => {
        this.socioSeleccionado = null;
        this.ngOnInit();
        this.ngAfterViewInit();
      },
      (err) => this.error = err
    );
  }

}
