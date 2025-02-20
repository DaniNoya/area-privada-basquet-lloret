<?php
include_once 'constants.php';
class Result {
  public $resultat = "";
  public $causa = "";
  public $token = "";
  /**
   * @var array
   */
  public $persona;
  public $socio;
  public $porcentajeDescuento;
  public $anioPasadoDescuento;
  public $temporadas;
  public $equipos;
  public $familiares;
  public $jugadores;
  public $entrenadores;
  public $directivos;
  public $cargos;
  public $sexos;
  public $niveles_formacion;
  public $tipos_parentesco;
  public $categorias;
  public $competiciones;
  public $tiposCategoria;
  public $jugadoresAsignados;
  public $jugadoresDisponibles;
  public $jugadoresDisponiblesJovenes;
  public $entrenadoresAsignados;
  public $entrenadoresDisponibles;
  public $familiaresAsignados;
  public $familiaresDisponibles;
  public $directivosAsignados;
  public $directivosDisponibles;
  /**
   * @var array
   */
  public $imagenes;
  public $twitter;
  public $facebook;
  public $instagram;
  public $google;
  public $apis;
  public $status;
  public $url;
  public $errMessage;
  public $idUsuario;
  public $modulos;
  public $importe;
}
class ResultImportesDescuentos {
  public $sql;
  public $jugador;
  public $players;
  public $amountOnline;
  public $amountPresencial;
  public $amountOnlineInscription;
  public $amountDiscountAreBrothers;
  public $amountSinglePaymentDiscount;
  public $amountDiscountIsMember;
  public $sonHermanos;
  public $temporadaPasada;
  public $isSocioClub;
  //public $temporadaImporte;
  public $concepto;
  public $importe;
  public $importeInscripcion;
  //public $importeConDescuentoTemporadaPasda;
  //public $importeConDescuentosAplicados;
  //public $restante;
  //public $total;
  /*public $esDescuento;
  public $esPorcentaje;*/
  //public $porcentajeDescuentoUnico;
  //public $precioDescunetPagoUnico;
  //public $porcentajeDescuentoHermanos;
  //public $precioDescunetHermano;
  public $precioDescunetEsSocio;
  public $porcentajeDescuentoPrimerE;
  public $porcentajeDescuentoTabDescuentos;
  public $anioPasadoDescuento;
}
function returnConection() {
  if (TEST_APP)
    //$con=mysqli_connect("81.46.246.126","basquetlloret","W1f1Nu7s2017","basquetlloretdb_test","33077");
    $con=mysqli_connect("localhost","root","","test_basquetlloret","3306");
  else  //si estamos en produccion TEST_APP vale false
    $con=mysqli_connect("localhost","root","","test_basquetlloret","3306");
  return $con;
}
?>