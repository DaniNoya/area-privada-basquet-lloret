import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import {Usuario} from '../classes/usuario';
import {environment} from '../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class LoginService {

  private API_URL = environment.API_URL;

  constructor(private http: HttpClient) { }

  login(usuario: Usuario) {
    return this.http.post(this.API_URL + '/login.php', JSON.stringify(usuario));
  }

  sendMailValidateEmail(usuario) {
    return this.http.post(this.API_URL + '/forgotPassword.php', {'tipo':'confirmEmail','dataUsuario':usuario});
  }
}
