import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';
import { FormControl, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { ForgotPasswordService } from './forgot-password.service';

@Component({
  selector: 'app-forgot-password',
  templateUrl: './forgot-password.component.html',
  styleUrls: ['./forgot-password.component.css']
})
export class ForgotPasswordComponent implements OnInit {

  @ViewChild('username') private inputName: ElementRef;
  forgotPasswordForm = new FormGroup({
    username: new FormControl('', Validators.required),
    email: new FormControl('', Validators.required)
  });
  
  successForgotPassword: string;

  // Variable on mostrarem l'error
  errorForgotPassword: string;

  spinnerSuccess = false;
  visible = true;
  btnValidarEmail = false;

  constructor(private forgotPasswordService: ForgotPasswordService, public router: Router) { }

  ngOnInit() {
    this.inputName.nativeElement.focus();
  }

  enviar(usuario) {
    this.successForgotPassword = '';
    this.visible = true;
    this.forgotPasswordService.checkData(usuario).subscribe(response => {
      if (response['resultat'] === 'OK') {
        this.errorForgotPassword = '';
        this.visible = false;
        this.spinnerSuccess = true;
        this.forgotPasswordService.sendMailRestorePassword(usuario).subscribe(
          () => {
            this.spinnerSuccess = false;
            this.successForgotPassword = 'Recibirá un correo para poder modificar la contraseña';
          },
          (err) => {
            this.spinnerSuccess = false;
            this.visible = true;
            this.errorForgotPassword = 'Error al enviar el correo';
          }
        );
      } else {
        this.successForgotPassword = '';
        this.errorForgotPassword = response['causa'];
        if (response['causa'] == "Falta validar el email") {
          this.btnValidarEmail = true;
        } else {
          this.btnValidarEmail = false;
        }
      }
    });
  }

  volverLogin() {
    this.router.navigateByUrl('/login');
  }

  clickInputUsername() {
    this.errorForgotPassword = '';
    this.btnValidarEmail = false;
  }
  clickInputEmail() {
    if (this.errorForgotPassword == "La combinación de Usuario/Correo no es correcta") {
      this.errorForgotPassword = '';
    }
  }

  validarEmail(usuario) {
    //this.visible = false;
    this.errorForgotPassword = '';
    this.spinnerSuccess = true;
    this.forgotPasswordService.sendMailValidateEmail(usuario).subscribe(
      () => {
        this.errorForgotPassword = '';
        this.spinnerSuccess = false;
        this.visible = false;
        this.successForgotPassword = 'Recibirá un correo para poder validar el correo electrónico del usuario';
      },
      (err) => {
        //this.visible = true;
        this.spinnerSuccess = false;
        this.errorForgotPassword = 'Error al enviar el correo';
      }
    );
  }

}
