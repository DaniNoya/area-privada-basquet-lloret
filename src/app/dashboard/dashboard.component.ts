import {Component, ElementRef, HostListener, OnInit, ViewChild} from '@angular/core';
import {ModulosService} from './modulos.service';
import {Usuario} from '../classes/usuario';
import {Modulo} from '../classes/modulo';
import {Router} from '@angular/router';
import {AppComponent} from '../app.component';
import {GlobalService} from './global.service';
import {ErrorDialogComponent} from "./dialogs/error-dialog/error-dialog.component";
import {MatDialog} from "@angular/material/dialog";
import {NgxSpinnerService} from "ngx-spinner";

@Component({
    selector: 'app-dashboard',
    templateUrl: './dashboard.component.html',
    styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

    spinnerMessage = '';
    modulos: Modulo[] = [];
    titulo = 'Basquet Lloret';
    subtitulo = 'Área de miembros';
    menuObert = true;
    maxWidth = 992;
    @HostListener('window:resize', ['$event'])
    onResize(event) {
      if (window.innerWidth < this.maxWidth) {
        this.menuObert = false;
      } else {
        this.menuObert = true;
      }
    }

    constructor(private moduloService: ModulosService,
                private router: Router,
                public appComponent: AppComponent,
                private globalService: GlobalService,
                public dialog: MatDialog,
                private spinner: NgxSpinnerService) { }

    ngOnInit() {
      if (window.innerWidth < this.maxWidth) {
        this.menuObert = false;
      } else {
        this.menuObert = true;
      }
      this.getModulos();
    }

    toogleMenu() {
      if (window.innerWidth < this.maxWidth) {
        this.menuObert = !this.menuObert;
      }
    }

    getModulos() {
      //console.log(this.appComponent.idUsuario);
      this.moduloService.getModulos(this.appComponent.idUsuario).subscribe(
        items => {this.modulos = items}
      );
    }

    integrar(xarxa: string) {
        /*this.spinnerMessage = 'Redireccionando...';
        this.spinner.show();
        this.globalService.integrate(xarxa).subscribe(result => {
          if (result['status'] === "OK"){
            this.spinner.hide();
            window.open(result['url'], '_top');
          } else {
            this.spinner.hide();
            let dialogRef = this.dialog.open(ErrorDialogComponent, {
              disableClose: false,
              width: '400px'
            });
            dialogRef.componentInstance.errorMessage = result['errMessage'];
            dialogRef.afterClosed().subscribe(() => dialogRef = null);
          }
        });*/
    }

    logout() {
      this.appComponent.idUsuario = null;
      localStorage.clear();
      this.router.navigate(['login']);
    }
}
