import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Usuario } from '../classes/usuario';
import { environment } from '../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class ForgotPasswordService {

  private API_URL = environment.API_URL;

  constructor(private http: HttpClient) { }


  checkData(usuario) {
    return this.http.post(this.API_URL + '/forgotPassword.php', JSON.stringify(usuario));
  }

  sendMailRestorePassword(usuario) {
    return this.http.put(this.API_URL + '/forgotPassword.php', JSON.stringify(usuario));
  }
  
  sendMailValidateEmail(usuario) {
    return this.http.post(this.API_URL + '/forgotPassword.php', {'tipo':'confirmEmail','dataUsuario':usuario});
  }
}
