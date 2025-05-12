import {Component, ElementRef, OnInit, ViewChild} from '@angular/core';
import { FormControl, FormGroup, Validators} from '@angular/forms';
import {LoginService} from './login.service';
import {Router} from '@angular/router';
import { AppComponent } from '../app.component';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css']
})
export class LoginComponent implements OnInit {

  @ViewChild('username') private inputName: ElementRef;
  loginForm = new FormGroup({
    username: new FormControl('', Validators.required),
    password: new FormControl('', Validators.required),
    recordarUsuario: new FormControl()
  });
  errorLogin: string;
  btnValidarEmail = false;
  spinnerSuccess = false;

  visible = true;
  successValidate: string;

  constructor(private loginService: LoginService, public router: Router, private appComponent: AppComponent) { }

  ngOnInit() {
    this.inputName.nativeElement.focus();
  }

  entrar(usuario) {
    // const resposta = new Resposta();
    this.successValidate = '';
    this.visible = true;
    this.loginService.login(usuario).subscribe(response => {
      if (response['resultat'] === 'OK') {
        localStorage.setItem('token', response['token']);
        localStorage.setItem('idU', response['idUsuario']);
        this.appComponent.idUsuario = parseInt(response['idUsuario']);
        this.errorLogin = '';
        this.router.navigateByUrl('/');
      } else {
        this.errorLogin = response['causa'];
        if (response['causa'] == "Falta validar el email") {
          this.btnValidarEmail = true;
        } else {
          this.btnValidarEmail = false;
        }
      }
    });
  }

  clickInputUsername() {
    this.errorLogin = '';
    this.btnValidarEmail = false;
  }
  clickInputPassword() {
    if (this.errorLogin == "La combinación de Usuario/Contraseña no es correcta") {
      this.errorLogin = '';
    }
  }

  validarEmail(usuario) {
    this.errorLogin = '';
    this.spinnerSuccess = true;
    this.loginService.sendMailValidateEmail(usuario).subscribe(
      () => {
        this.errorLogin = '';
        this.spinnerSuccess = false;
        this.visible = false;
        this.successValidate = 'Recibirá un correo para poder validar el correo electrónico del usuario';
      },
      (err) => {
        this.spinnerSuccess = false;
        this.errorLogin = 'Error al enviar el correo';
      }
    );
  }
  
}
