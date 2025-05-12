import {Component, ElementRef, Inject, OnInit, ViewChild} from '@angular/core';
import {MAT_DIALOG_DATA, MatButton, MatDialog} from '@angular/material';
import {Sexo} from '../../../classes/sexo';
import {GlobalService} from '../../global.service';
import {FamiliaresService} from '../../familiares/familiares.service';
import {NgxSpinnerService} from 'ngx-spinner';

@Component({
  selector: 'app-editar-familiar-dialog',
  templateUrl: './editar-familiar-dialog.component.html',
  styleUrls: ['./editar-familiar-dialog.component.css']
})
export class EditarFamiliarDialogComponent implements OnInit {

  // Array de sexes
  sexos: Sexo[] = [];

  // Variable on mostrarem l'error, en cas que n'hi hagi
  error: string;

  constructor(@Inject(MAT_DIALOG_DATA) public data,
              public dialog: MatDialog,
              private globalService: GlobalService,
              private familiaresService: FamiliaresService,
              public spinner: NgxSpinnerService) { }

  ngOnInit() {
    // Obtenim els sexes
    this.globalService.getSexos().subscribe((res) => this.sexos = res);
  }

  public save() {
    this.error = '';
    this.spinner.show();
    this.familiaresService.update(this.data.familiar)
      .subscribe(
        (res) => {
          document.getElementById('close').click();
          this.spinner.hide();
        },
        (err) => {
          this.error = err;
          this.spinner.hide();
        }
      );
  }
}
