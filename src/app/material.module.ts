import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import {MatIconModule} from '@angular/material/icon';
import {
  MatDatepickerModule,
  MatDialogModule,
  MatFormFieldModule,
  MatPaginatorModule,
  MatTableModule,
  MAT_DATE_LOCALE,
  MatButtonModule,
  MatCardModule,
  MatCheckboxModule,
  MatListModule,
  MatProgressSpinnerModule,
  MatSelectModule,
  MatSortModule,
  MatTabsModule,
  MatExpansionModule
} from '@angular/material';
import {MatInputModule} from '@angular/material/input';
import {MatNativeDateModule} from '@angular/material/core';

@NgModule({
  declarations: [],
  imports: [
    CommonModule,
    MatIconModule,
    MatTableModule,
    MatPaginatorModule,
    MatFormFieldModule,
    MatDialogModule,
    MatDatepickerModule,
    MatProgressSpinnerModule,
    MatSortModule,
    MatListModule,
    MatTabsModule,
    MatSelectModule,
    MatCheckboxModule,
    MatButtonModule,
    MatCardModule,
    MatInputModule,
    MatNativeDateModule,
    MatExpansionModule
  ],
  exports: [
    MatIconModule,
    MatTableModule,
    MatPaginatorModule,
    MatFormFieldModule,
    MatDialogModule,
    MatDatepickerModule,
    MatProgressSpinnerModule,
    MatSortModule,
    MatListModule,
    MatTabsModule,
    MatSelectModule,
    MatCheckboxModule,
    MatButtonModule,
    MatCardModule,
    MatInputModule,
    MatNativeDateModule,
    MatExpansionModule
  ],
  providers: [
    {provide: MAT_DATE_LOCALE, useValue: 'es-ES'}
  ]
})
export class MaterialModule { }
