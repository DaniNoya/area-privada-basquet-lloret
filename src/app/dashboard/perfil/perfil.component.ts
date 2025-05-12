import {AfterViewInit, Component, ElementRef, Input, OnInit, ViewChild} from '@angular/core';
import {MatDialog, MatPaginator, MatSort, MatTableDataSource} from '@angular/material';
import {Perfil} from '../../classes/Perfil';
import {PerfilService} from '../perfil/perfil.service';
import {Jugador} from '../../classes/jugador';
import {JugadoresService} from '../jugadores/jugadores.service';
import { Socio } from '../../classes/socio';
import {FamiliaresService} from '../familiares/familiares.service';
import {TiposParentesco} from '../../classes/tipos_parentesco';
import {ErrorDialogComponent} from '../dialogs/error-dialog/error-dialog.component';
import {GlobalService} from '../global.service';
import {Sexo} from '../../classes/sexo';
import {BajaDialogComponent} from '../dialogs/baja-dialog/baja-dialog.component';
import {formatDate} from '@angular/common';
import { NgxSpinnerService } from 'ngx-spinner';
import { AppComponent } from 'src/app/app.component';

@Component({
  selector: 'app-perfil',
  templateUrl: './perfil.component.html',
  styleUrls: ['./perfil.component.css']
})
export class PerfilComponent implements OnInit {

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
  dataSource = new MatTableDataSource<Perfil>();

  // Objecte que emmagatzema les dades per la taula
  dataSourceJ = new MatTableDataSource<Jugador>();
  // Columnes que mostrarem a la taula
  displayedColumnsJ: string[] = ['nombre', 'primer_apellido', 'segundo_apellido', 'fecha_nacimiento', 'sexo', 'observaciones', 'actions'];

  // Array de parentescos per mostrar en el modal
  tiposParentesco: TiposParentesco[] = [];
  parentescoSeleccionado: number;

  // Objecte descuento, que servirà de pivot per tractar les dades
  personaSeleccionada: Perfil = new Perfil;
  jugadorSeleccionado: Jugador;

  // Variables per mostrar / amagar contingut del template
  editarPersona = false;

  editarJugador = false;
  crearJugador = false;

  // Mode de visualització per defecte
  metodoVisualizacion = 'alta';

  // Mode de visualització per mostrar els que no tenen familiars assignats
  sinFamiliaresAsignados = false;

  // Mode de visualització per mostrar els que no estan assignats a cap equip
  sinEquiposAsignados = false;

  soyTutor = false;

  isSocio = false;
  isSocioInTemporada = false;
  socio: Socio = new Socio;

  // Variable on mostrarem l'error, en cas que n'hi hagi
  error: string;
  errorJugador: string;

  // Array de sexes
  sexos: Sexo[] = [];

  constructor(private perfilService: PerfilService,
              private jugadoresService: JugadoresService,
              private globalService: GlobalService,
              public dialog: MatDialog,
              private familiaresService: FamiliaresService,
              private spinner: NgxSpinnerService,
              private appComponent: AppComponent) { }

  ngOnInit() {
    this.perfilService.getPersona(this.appComponent.idUsuario).subscribe(res => {
      this.isLoadingResults = false;
      this.personaSeleccionada = res as Perfil;
      const year = new Date().getFullYear();
      const month = new Date().getMonth();
      const day = new Date().getDate();
      const today = new Date(year, month, day, 0, 0, 0, 0).getTime();
      const birthday = new Date(this.personaSeleccionada.fecha_nacimiento);
      birthday.setHours(0, 0, 0, 0);
      this.personaSeleccionada.edad = Math.floor((today - birthday.getTime()) / 1000 / 3600 / 24 / 365);
      //console.log(res)
    });

    this.perfilService.getJugadores(this.appComponent.idUsuario,this.metodoVisualizacion).subscribe(res => {
      this.dataSourceJ.data = res as Jugador[];
      this.isLoadingResults = false;
      //console.log(res)
    });

    this.perfilService.getIsSocio(this.appComponent.idUsuario).subscribe((res) => {
      this.isSocio = res;
      //console.log(res);

      if (this.isSocio == true) {
        this.perfilService.getIsSocioInTemporada(this.appComponent.idUsuario).subscribe((res) => {
          this.isSocioInTemporada = res;
        });
        this.perfilService.getSocio(this.appComponent.idUsuario).subscribe((res) => {
          this.socio = res as Socio;
          //console.log(this.socio);
        });
      }
    });

    this.perfilService.getEsTutor(this.appComponent.idUsuario).subscribe(res => {
      this.soyTutor = res;
      //console.log(res)
    });
    // Obtenim els sexes
    this.globalService.getSexos().subscribe((res) => this.sexos = res);
    // Obtenemos los tipos de parentesco
    this.familiaresService.getTipos().subscribe(items => this.tiposParentesco = items);
    // Format de Sorting, canviem els valors a minuscules per ordenar
    this.dataSourceJ.sortingDataAccessor = (item, property) => {
      return item[property].toString().toLowerCase();
    };
    // Columnes on aplicarà el filtre
    this.dataSourceJ.filterPredicate = (data, filter: string): boolean => {
      return data.nombre.toLowerCase().includes(filter) || data.primer_apellido.toLowerCase().includes(filter) ||
        data.segundo_apellido.toLowerCase().includes(filter) || data.fecha_nacimiento.toLowerCase().includes(filter);
    };
  }

  ngAfterViewInit() {
    // Assignem els elements del template (sort i paginator) al component d'ordenar i paginar del datasource
    this.dataSourceJ.sort = this.sort;
    this.dataSourceJ.paginator = this.paginator;
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
    this.dataSourceJ.filter = value.trim().toLocaleLowerCase();
  }

  public showItem = (jugador: Jugador) => {
    this.errorJugador = '';
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

  public startEdit = (jugador: Jugador) => {
    this.errorJugador = '';
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

  public editar() {
    this.editarPersona = true;
  }
  public editarJ() {
    this.editarJugador = true;
  }

  public save() {
    this.error = '';
    this.spinner.show();
    this.perfilService.update(this.personaSeleccionada)
      .subscribe(
        () => {
          this.spinner.hide();
        },
        (err) => {
          this.spinner.hide();
          this.error = err;
        }
      );
  }

  public saveJugador() {
    this.errorJugador = '';
    this.spinner.show();
    this.perfilService.updateJugador(this.jugadorSeleccionado)
      .subscribe(
        () => {
          this.volver();
          this.spinner.hide();
        },
        (errJ) => {
          this.spinner.hide();
          this.errorJugador = errJ;
        }
      );
  }

  public store() {
    this.errorJugador = '';

    this.perfilService.store(this.appComponent.idUsuario, this.parentescoSeleccionado, this.jugadorSeleccionado)
      .subscribe(
        (res) => {
          this.volver();
        },
        (errJ) => this.errorJugador = errJ
      );
  }

  public startAdd() {
    this.errorJugador = '';
    this.jugadorSeleccionado = new Jugador();
    this.crearJugador = true;
  }

}
