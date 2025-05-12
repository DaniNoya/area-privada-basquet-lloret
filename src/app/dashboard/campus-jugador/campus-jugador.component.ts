import { Component, OnInit, ViewChild, ElementRef, AfterViewInit } from '@angular/core';
import { MatDialog, MatPaginator, MatSort, MatTableDataSource } from '@angular/material';
import { CampusJugadorService } from './campus-jugador.service';
import { Jugador } from '../../classes/jugador';
import { JugadorCampus } from '../../classes/jugador-campus';
import { GlobalService } from '../global.service';
import { NgxSpinnerService } from 'ngx-spinner';
import { FormControl } from '@angular/forms';
import * as XLSX from 'xlsx';

@Component({
  selector: 'app-campus-jugador',
  templateUrl: './campus-jugador.component.html',
  styleUrls: ['./campus-jugador.component.css']
})
export class CampusJugadorComponent implements OnInit, AfterViewInit {

  isLoadingResults = true;

  // Lectura de objetos del template
  @ViewChild(MatPaginator) paginator: MatPaginator;
  @ViewChild(MatSort) sort: MatSort;
  @ViewChild('filtrar') input: ElementRef;

  // Objeto que almacena los datos para la tabla
  dataSource = new MatTableDataSource<JugadorCampus>();
  
  // Columnas que mostraremos en la tabla
  displayedColumns: string[] = ['nombre', 'primer_apellido', 'segundo_apellido', 'fecha_nacimiento', 'sexo', 'semanas', 'actions'];

  // Filtros
  filtroNombre = new FormControl('');
  filtroSemana = new FormControl(0);
  
  // Array de semanas disponibles
  semanas = [
    { id: 1, nombre: 'Semana 1' },
    { id: 2, nombre: 'Semana 2' },
    { id: 3, nombre: 'Semana 3' },
    { id: 4, nombre: 'Semana 4' },
    { id: 5, nombre: 'Semana 5' },
    { id: 6, nombre: 'Semana 6' },
    { id: 7, nombre: 'Semana 7' },
    { id: 8, nombre: 'Semana 8' },
    { id: 9, nombre: 'Semana 9' }
  ];

  constructor(
    private campusJugadorService: CampusJugadorService,
    private globalService: GlobalService,
    private spinner: NgxSpinnerService,
    public dialog: MatDialog
  ) { }

  ngOnInit() {
    this.cargarJugadores();

    // Suscribirse a cambios en los filtros
    this.filtroNombre.valueChanges.subscribe(() => {
      this.aplicarFiltros();
    });

    this.filtroSemana.valueChanges.subscribe(() => {
      this.aplicarFiltros();
    });
  }

  ngAfterViewInit() {
    this.dataSource.paginator = this.paginator;
    this.dataSource.sort = this.sort;
  }

  /**
   * Carga los jugadores inscritos en el Casal d'Estiu
   */
  cargarJugadores() {
    this.isLoadingResults = true;
    this.spinner.show();

    this.campusJugadorService.getJugadoresCampus(
      this.filtroNombre.value,
      this.filtroSemana.value
    ).subscribe(
      (data: JugadorCampus[]) => {
        this.dataSource.data = data;
        this.isLoadingResults = false;
        this.spinner.hide();
      },
      error => {
        console.error('Error al cargar jugadores del campus:', error);
        this.isLoadingResults = false;
        this.spinner.hide();
        this.globalService.mostrarMensaje('Error al cargar los datos de jugadores del campus');
      }
    );
  }

  /**
   * Aplica los filtros de nombre y semana
   */
  aplicarFiltros() {
    this.cargarJugadores();
  }

  /**
   * Limpia todos los filtros aplicados
   */
  limpiarFiltros() {
    this.filtroNombre.setValue('');
    this.filtroSemana.setValue(0);
    this.cargarJugadores();
  }

  /**
   * Obtiene las semanas de un jugador como texto formateado
   * @param jugador El jugador del que queremos obtener las semanas
   */
  obtenerSemanasTexto(jugador: JugadorCampus): string {
    if (!jugador.semanas || jugador.semanas.length === 0) {
      return 'Ninguna';
    }

    return jugador.semanas.map(s => `Semana ${s.id_semana}`).join(', ');
  }

  /**
   * Exporta los datos de la tabla a un archivo Excel
   */
  exportarExcel() {
    this.spinner.show();
    
    // Preparar los datos para exportar
    const datosExportar = this.dataSource.data.map(jugador => {
      return {
        'Nombre': jugador.nombre,
        'Primer Apellido': jugador.primer_apellido,
        'Segundo Apellido': jugador.segundo_apellido || '',
        'Fecha Nacimiento': jugador.fecha_nacimiento,
        'Sexo': jugador.sexo,
        'DNI': jugador.dni || '',
        'Teléfono': jugador.telefono1 || '',
        'Email': jugador.email || '',
        'Semanas': this.obtenerSemanasTexto(jugador)
      };
    });

    // Crear el libro de Excel
    const workbook = XLSX.utils.book_new();
    const worksheet = XLSX.utils.json_to_sheet(datosExportar);
    
    // Añadir la hoja al libro
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Jugadores Campus');
    
    // Generar el archivo y descargarlo
    const fechaActual = new Date().toISOString().slice(0, 10);
    XLSX.writeFile(workbook, `Jugadores_Campus_${fechaActual}.xlsx`);
    
    this.spinner.hide();
  }
}