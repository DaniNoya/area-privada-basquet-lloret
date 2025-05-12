import {Component, OnInit} from '@angular/core';
import {MatDialogRef} from '@angular/material';
import {FormControl} from '@angular/forms';

@Component({
  selector: 'app-baja-dialog',
  templateUrl: './baja-dialog.component.html',
  styleUrls: ['./baja-dialog.component.css']
})
export class BajaDialogComponent implements OnInit {

  fecha_baja = new FormControl(new Date());

  constructor(public dialogRef: MatDialogRef<BajaDialogComponent>) { }

  public jugador: string;

  ngOnInit() {
  }
}
