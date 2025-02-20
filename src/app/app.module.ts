import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';
import { HttpClientModule} from '@angular/common/http';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import {SidebarModule} from 'ng-sidebar';

import { AppRoutingModule } from './app.routing';

// ngx-spinner
import { NgxSpinnerModule } from 'ngx-spinner';

// Components aplicació
import { AppComponent } from './app.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { LoginComponent } from './login/login.component';
import { NotFoundComponent } from './not-found/not-found.component';
import { JugadoresComponent } from './dashboard/jugadores/jugadores.component';
import { FamiliaresComponent } from './dashboard/familiares/familiares.component';
import { EntrenadoresComponent } from './dashboard/entrenadores/entrenadores.component';
import { DirectivosComponent } from './dashboard/directivos/directivos.component';
import { FamiliaresDialogComponent } from './dashboard/dialogs/familiares-dialog/familiares-dialog.component';
import { ConfirmDialogComponent } from './dashboard/dialogs/confirm-dialog/confirm-dialog.component';
import { ErrorDialogComponent } from './dashboard/dialogs/error-dialog/error-dialog.component';
import { BajaDialogComponent } from './dashboard/dialogs/baja-dialog/baja-dialog.component';
import { EditarFamiliarDialogComponent } from './dashboard/dialogs/editar-familiar-dialog/editar-familiar-dialog.component';
import { TemporadasComponent } from './dashboard/temporadas/temporadas.component';
import { CrearTemporadaComponent } from './dashboard/dialogs/crear-temporada/crear-temporada.component';
import { CrearEquipoComponent } from './dashboard/dialogs/crear-equipo/crear-equipo.component';
import { EditarJugadorDialogComponent } from './dashboard/dialogs/editar-jugador-dialog/editar-jugador-dialog.component';
import { EditarDorsalesComponent } from './dashboard/dialogs/editar-dorsales/editar-dorsales.component';
import { EditarEntrenadorDialogComponent } from './dashboard/dialogs/editar-entrenador-dialog/editar-entrenador-dialog.component';
import { EditarDirectivoDialogComponent } from './dashboard/dialogs/editar-directivo-dialog/editar-directivo-dialog.component';


// JSON Web Token
import {JWT_OPTIONS, JwtModule} from '@auth0/angular-jwt';

// Bootstrap plugins
import { BsDropdownModule } from 'ngx-bootstrap/dropdown';
import { TooltipModule } from 'ngx-bootstrap/tooltip';
import { ModalModule } from 'ngx-bootstrap/modal';

// Components Material Angular
import {MaterialModule} from './material.module';

// Component camp text enriquit
import {AngularEditorModule} from '@kolkov/angular-editor';

// Component retocs imatges
import {ImageUploadModule} from 'ng2-imageupload';

// Serveis
import {LoginService} from './login/login.service';
import { ImagenesComponent } from './dashboard/imagenes/imagenes.component';
import { NoticiasComponent } from './dashboard/noticias/noticias.component';
import { PerfilComponent } from './dashboard/perfil/perfil.component';
import { DescuentosComponent } from './dashboard/descuentos/descuentos.component';
import { PagosComponent } from './dashboard/pagos/pagos.component';
import { PagosUsuarioComponent } from './dashboard/pagos-usuario/pagos-usuario.component';
import {MatAutocompleteModule} from '@angular/material/autocomplete';
import { ImportesComponent } from './dashboard/importes/importes.component';
import { PagosUsuarioDialogComponent } from './dashboard/dialogs/pagos-usuario-dialog/pagos-usuario-dialog.component';
import { ValidarPermisoComponent } from './dashboard/dialogs/validar-permiso/validar-permiso.component';
import { PartidosComponent } from './dashboard/partidos/partidos.component';
import { ForgotPasswordComponent } from './forgot-password/forgot-password.component';
import { SociosComponent } from './dashboard/socios/socios.component';




export function jwtOptionsFactory(tokenService) {
  return {
    tokenGetter: () => {
      return localStorage.getItem('token');
    }
  };
}

// @ts-ignore
// @ts-ignore
@NgModule({
  declarations: [
    AppComponent,
    DashboardComponent,
    LoginComponent,
    NotFoundComponent,
    JugadoresComponent,
    FamiliaresComponent,
    EntrenadoresComponent,
    DirectivosComponent,
    FamiliaresDialogComponent,
    ConfirmDialogComponent,
    ErrorDialogComponent,
    BajaDialogComponent,
    EditarFamiliarDialogComponent,
    TemporadasComponent,
    CrearTemporadaComponent,
    CrearEquipoComponent,
    EditarJugadorDialogComponent,
    EditarDorsalesComponent,
    EditarEntrenadorDialogComponent,
    EditarDirectivoDialogComponent,
    ImagenesComponent,
    NoticiasComponent,
    PerfilComponent,
    DescuentosComponent,
    PagosComponent,
    PagosUsuarioComponent,
    ImportesComponent,
    PagosUsuarioDialogComponent,
    ValidarPermisoComponent,
    PartidosComponent,
    ForgotPasswordComponent,
    SociosComponent
  ],
  entryComponents: [
    ValidarPermisoComponent,
    FamiliaresDialogComponent,
    PagosUsuarioDialogComponent,
    ConfirmDialogComponent,
    ErrorDialogComponent,
    BajaDialogComponent,
    EditarFamiliarDialogComponent,
    CrearTemporadaComponent,
    CrearEquipoComponent,
    EditarJugadorDialogComponent,
    EditarDorsalesComponent,
    EditarEntrenadorDialogComponent,
    EditarDirectivoDialogComponent],
    imports: [
        BrowserModule,
        HttpClientModule,
        FormsModule,
        AppRoutingModule,
        JwtModule.forRoot({
            jwtOptionsProvider: {
                provide: JWT_OPTIONS,
                useFactory: jwtOptionsFactory,
                deps: [LoginService]
            }
        }),
        ReactiveFormsModule,
        BsDropdownModule.forRoot(),
        TooltipModule.forRoot(),
        ModalModule.forRoot(),
        BrowserAnimationsModule,
        MaterialModule,
        NgxSpinnerModule,
        AngularEditorModule,
        ImageUploadModule,
        SidebarModule.forRoot(),
        MatAutocompleteModule
    ],
  providers: [
    LoginService
  ],
  bootstrap: [
    AppComponent
  ]
})
export class AppModule { }
