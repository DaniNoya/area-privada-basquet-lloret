import {AfterViewInit, Component, ElementRef, Input, OnInit, ViewChild} from '@angular/core';
import {MatDialog, MatPaginator, MatSort, MatTableDataSource, MatExpansionModule} from '@angular/material';
import {ContentsArrayFinal} from '../../classes/contentsArrayFinalPagosUsuario';
import {Pago} from '../../classes/pago';
import {PagosUsuarioService} from './pagos-usuario.service';
import {Jugador} from '../../classes/jugador';
import {JugadoresService} from '../jugadores/jugadores.service';
import {Temporada} from '../../classes/temporada';
import {TemporadasService} from '../temporadas/temporadas.service';
import {TipoPago} from '../../classes/tipo_pago';

import {ErrorDialogComponent} from '../dialogs/error-dialog/error-dialog.component';
import {GlobalService} from '../global.service';
import {BajaDialogComponent} from '../dialogs/baja-dialog/baja-dialog.component';
import {formatDate} from '@angular/common';
import { NgxSpinnerService } from 'ngx-spinner';
import { AppComponent } from 'src/app/app.component';

@Component({
  selector: 'app-pagos-usuario',
  templateUrl: './pagos-usuario.component.html',
  styleUrls: ['./pagos-usuario.component.css']
})
export class PagosUsuarioComponent implements OnInit {

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
  //dataSource = new MatTableDataSource<ContentsArrayFinal>();

  arrayFinal: ContentsArrayFinal[]; 

  // Columnes que mostrarem a la taula
  displayedColumns: string[] = ['concepto','fecha','importe'];

  // Objecte pago, que servirà de pivot per tractar les dades
  pagoUsuarioSeleccionado: ContentsArrayFinal;

  // Almacena todas las temporadas
  temporadas: Temporada[] = [];
  temporadaActual: string;

  // Variables per mostrar / amagar contingut del template
  editarPagoUsuario = false;
  crearPagoUsuario = false;

  // Mode de visualització per defecte
  metodoVisualizacion = 'todo';

  // Variable on mostrarem l'error, en cas que n'hi hagi
  error: string;

  // Array de tipo pago
  tiposPago: TipoPago[] = [];

  soyTutor = true;

  seleccionado:string;

  constructor(private pagosUsuarioService: PagosUsuarioService,
              private jugadoresService: JugadoresService,
              private temporadasService: TemporadasService,
              private globalService: GlobalService,
              public dialog: MatDialog,
              private spinner: NgxSpinnerService,
              private appComponent: AppComponent) { }

  ngOnInit() {
    // Mostrem l'spinner de carga
    this.isLoadingResults = true;
    if (this.modal) { // Si es modal eliminem el seu propi element i els familiars ja assignats
      let exclusionList = '(';
      this.modalObert.familiaresAssignados.forEach((v, k) => {
        exclusionList += v.id + ',';
      });
      exclusionList += this.addTo.id + ')';
      this.pagosUsuarioService.getPagos(this.appComponent.idUsuario, this.metodoVisualizacion, exclusionList).subscribe(res => {
        setTimeout(() => {
          this.arrayFinal = res as ContentsArrayFinal[];
          for(let tipo of this.arrayFinal) {
            for(let persona of tipo.data) {
              persona.importesSelect = [];
              for (let i = 50; i <= persona.restante; i += 50) {
                if((i + 50) <= persona.restante){
                  persona.importesSelect.push(i);
                }
              }
              persona.importesSelect.push(persona.restante);
            }
          }
          console.log(this.arrayFinal);
          this.isLoadingResults = false;
        }, 500);
      });
    } else {
      // Obtenim el llistat de directius
      this.pagosUsuarioService.getPagos(this.appComponent.idUsuario, this.metodoVisualizacion).subscribe(res => {
        this.arrayFinal = res as ContentsArrayFinal[];
        for(let tipo of this.arrayFinal) {
          for(let persona of tipo.data) {
            persona.importesSelect = [];
            for (let i = 50; i <= persona.restante; i += 50) {
              if((i + 50) <= persona.restante){
                persona.importesSelect.push(i);
              }
            }
            persona.importesSelect.push(persona.restante);
          }
        }
        console.log(this.arrayFinal);
        this.isLoadingResults = false;
      });
    }
  }

  public test() {
    console.log(this.arrayFinal);
    for(let concepto of this.arrayFinal){
      console.log(concepto);
    }
  }

  public realizarPago(idTipo, conceptoTipo, idJugador){
    this.error = '';
    this.spinner.show();
    this.pagosUsuarioService.store(idTipo, conceptoTipo, this.appComponent.idUsuario, idJugador, this.seleccionado) //comprobar con Aleix
      .subscribe(
        (res) => {
          //this.volver();
          if (res > 0) {
            const id = res;
            this.pagosUsuarioService.redirectPasarela({id: id});
          }
          //console.log(res);
        },
        (err) => {
          this.error = err
        }
      );
  }
}
