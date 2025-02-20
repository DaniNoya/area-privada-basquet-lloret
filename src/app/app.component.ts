import { Component } from '@angular/core';
import { Usuario } from './classes/usuario';
import {GlobalService} from './dashboard/global.service';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})
export class AppComponent {
  title = 'Basquet Lloret';
  color = 'primary';
  twitterApi = false;
  facebookApi = false;
  instagramApi = false;
  googleApi = false;
  idUsuario : number = parseInt(localStorage.getItem('idU'));

  constructor(private globalService: GlobalService) {}

  ngOnInit(){
    this.globalService.getApiConnections().subscribe(
      items => {
        this.twitterApi = items['twitter'];
        this.instagramApi = items['instagram'];
        this.facebookApi = items['facebook'];
        this.googleApi = items['google'];
      }
    );
  }
}
