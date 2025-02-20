import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import {LoginComponent} from './login/login.component';
import { ForgotPasswordComponent } from './forgot-password/forgot-password.component';
import {NotFoundComponent} from './not-found/not-found.component';
import { AuthGuardService as AuthGuard } from './auth/auth-guard.service';
import {JugadoresComponent} from './dashboard/jugadores/jugadores.component';
import {FamiliaresComponent} from './dashboard/familiares/familiares.component';
import {EntrenadoresComponent} from './dashboard/entrenadores/entrenadores.component';
import {DirectivosComponent} from './dashboard/directivos/directivos.component';
import { SociosComponent } from './dashboard/socios/socios.component';
import {TemporadasComponent} from './dashboard/temporadas/temporadas.component';
import {ImagenesComponent} from './dashboard/imagenes/imagenes.component';
import {NoticiasComponent} from './dashboard/noticias/noticias.component';
import {PerfilComponent} from './dashboard/perfil/perfil.component';
import {DescuentosComponent} from './dashboard/descuentos/descuentos.component';
import {PagosComponent} from './dashboard/pagos/pagos.component';
import {PagosUsuarioComponent} from './dashboard/pagos-usuario/pagos-usuario.component';
import { PartidosComponent } from './dashboard/partidos/partidos.component';

export const routes: Routes = [
  { path: 'login', component: LoginComponent, canActivate: [AuthGuard] },
  { path: 'forgot-password', component: ForgotPasswordComponent },
  { path: '',
    component: DashboardComponent,
    data: {
      title: 'Inicio'
    },
    children: [
      {
        path: 'perfil',
        component: PerfilComponent
      },
      {
        path: 'jugadores',
        component: JugadoresComponent
      },
      {
        path: 'familiares',
        component: FamiliaresComponent
      },
      {
        path: 'entrenadores',
        component: EntrenadoresComponent
      },
      {
        path: 'directivos',
        component: DirectivosComponent
      },
      {
        path: 'socios',
        component: SociosComponent
      },
      {
        path: 'temporadas',
        component: TemporadasComponent
      },
      {
        path: 'partidos',
        component: PartidosComponent
      },
      {
        path: 'imagenes',
        component: ImagenesComponent
      },
      {
        path: 'noticias',
        component: NoticiasComponent
      },
      {
        path: 'descuentos',
        component: DescuentosComponent
      },
      {
        path: 'pagos',
        component: PagosComponent
      },
      {
        path: 'pagos-usuario',
        component: PagosUsuarioComponent
      }
    ],
    canActivate: [AuthGuard] },

  { path: '**', component: NotFoundComponent }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
