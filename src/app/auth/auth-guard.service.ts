import { Injectable } from '@angular/core';
import {Router, CanActivate, ActivatedRouteSnapshot} from '@angular/router';
import { AuthService } from './auth.service';

@Injectable({
  providedIn: 'root'
})
export class AuthGuardService implements CanActivate {

  constructor(public auth: AuthService, public router: Router) { }

  canActivate(route: ActivatedRouteSnapshot): boolean {
    if (route.routeConfig.path === 'login') {
      if (this.auth.isLoggendId()) {
        this.router.navigate(['']);
        return false;
      }
    } else {
      if (!this.auth.isLoggendId()) {
        this.router.navigate(['login']);
        return false;
      }
    }
    return true;
  }
}
