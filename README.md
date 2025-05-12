# Basquet Lloret

Este proyecto es una aplicación web para el club de baloncesto Basquet Lloret, desarrollada con Angular y conectada a una base de datos MySQL.

## Requisitos

- **Node.js**: Versión 18.20
- **Angular CLI**: Versión 7.3.9
- **Base de datos**: MySQL

## Configuración

1. **API URL**:

   - **Desarrollo**: Modifica el archivo `src/environments/environment.ts` para establecer `API_URL`:
     ```typescript
     export const environment = {
       production: false,
       API_URL: 'http://localhost/basquetlloretWP/areaprivada/api'
     };
     ```
   - **Producción**: Modifica el archivo `src/environments/environment.prod.ts` para establecer `API_URL`:
     ```typescript
     export const environment = {
       production: true,
       API_URL: 'URL_DE_PRODUCCION_AQUÍ'
     };
     ```

2. **Conexión a la base de datos**:

   - **Desarrollo**: En el archivo `api/dbConnection.php`, configura la conexión a la base de datos de la siguiente manera:
     ```php
     $con = mysqli_connect("localhost", "root", "", "test_basquetlloret", "3306");
     ```
   - **Producción**: Actualiza los valores de la conexión según los detalles de la base de datos en producción.

## Puesta en marcha del proyecto

1. **Verificar la versión de Node.js**:
   Asegúrate de que la versión de Node.js instalada sea la **18.20**.

2. **Instalar y usar la versión correcta de Node.js**:
   Si tienes una versión diferente de Node.js:
   - Instala `nvm` siguiendo las instrucciones oficiales disponibles en su [repositorio en GitHub](https://github.com/nvm-sh/nvm).
   - Instala la versión 18 de Node.js utilizando el comando:
     ```
     nvm install 18
     ```
   - Para usar la versión 18, ejecuta:
     ```
     nvm use 18
     ```

3. **Instalar dependencias**:
   En la carpeta raíz del proyecto, instala todas las dependencias necesarias ejecutando el siguiente comando:
     ```
     npm install
     ```

4. **Iniciar el servidor de desarrollo**:
Para iniciar el servidor de desarrollo y levantar la aplicación, ejecuta el siguiente comando:
     ```
     ng serve
     ```

Si encuentras un error relacionado con OpenSSL, sigue estos pasos:
- Instala la herramienta `cross-env`:
  ```
  npm install --save-dev cross-env
  ```
- Luego, abre el archivo `package.json` y actualiza el script de inicio (`"start"`) para que luzca de la siguiente manera:
  ```json
  "scripts": {
    "start": "cross-env NODE_OPTIONS=--openssl-legacy-provider ng serve --open"
  }
  ```
- Ahora, para iniciar el proyecto, ejecuta:
  ```
  npm start
  ```

La aplicación estará disponible en la URL: `http://localhost:4200/`.

## Despliegue
Para desplegar la aplicación en un entorno de producción, sigue estos pasos:

1. **Construir la aplicación**:
   Ejecuta el siguiente comando para construir la aplicación:
     ```
     ng build --prod
     ```

# Mejoras en el Sistema de Cálculo de Importes
## Descripción
Se ha implementado una versión mejorada del sistema de cálculo de importes para resolver problemas con los descuentos y los importes restantes negativos.

## Cambios Realizados
```php
// Modify the calculation logic to ensure consistent discount application
$playersSurnames = [];
foreach ($registrationPlayers as $player) {
    $firstSurname = "";
    $secondSurname = "";
    foreach($player as $key => $value) {
        if ($key == "primerCognomJugador") $firstSurname = strtolower(trim($value));
        if ($key == "segonCognomJugador") $secondSurname = strtolower(trim($value));
    }

    // Only use first surname for sibling check to be more reliable
    $playersSurnames[] = str_replace(" ", "", $firstSurname);
}

// Initialize these properties before the foreach loop
$importe->amountOnline = 0;
$importe->amountPresencial = 0;
$importe->amountOnlineInscription = 0;
$importe->amountDiscountAreBrothers = 0;
$importe->amountSinglePaymentDiscount = 0;
$importe->amountDiscountIsMember = 0;

// Check for siblings once before processing individual players
$sonGermans = comprobarHermanos($playersSurnames);

// Calculate total amount for all players
$totalAmount = 0;
$players = [];

foreach ($registrationPlayers as $player) {
    // ... código existente para extraer datos del jugador ...

    // Use the sibling status determined once for all players
    $importe->sonHermanos = $sonGermans;

    $isSocioClub = esSocioClub($con, $isMas18 ? $dniPlayer : $dniTutor);
    $importe->isSocioClub = $isSocioClub;

    $temporadaPasadaStatus = comprobarTemporadaPasada($con, $dniPlayer);
    $importe->temporadaPasada = $temporadaPasadaStatus;

    // Start with the base price
    $precioUnitario = $precioQuotaAnual;

    // Apply previous season discount first if applicable
    if ($temporadaPasadaStatus === "OK") {
        $precioUnitario -= $precioDescuentoAnioPasado;
    }

    // Calculate all discounts based on the adjusted base price
    $precioDescunetHermano = $sonGermans ? round(($precioUnitario * $porcentajeDescuentoSonHermanos) / 100, 2) : 0;
    $precioDescunetPagoUnico = round(($precioUnitario * $porcentajeDescuentoPagoUnico) / 100, 2);
    $precioDescunetEsSocio = $isSocioClub ? round(($precioUnitario * $porcentajeDescuentoEsSocio) / 100, 2) : 0;

    // Store discount values
    $importe->precioDescunetHermano = $precioDescunetHermano;
    $importe->precioDescunetPagoUnico = $precioDescunetPagoUnico;
    $importe->precioDescunetEsSocio = $precioDescunetEsSocio;

    // Calculate total discount
    $totalDiscount = $precioDescunetPagoUnico + $precioDescunetHermano + $precioDescunetEsSocio;
    
    // Apply all discounts to get final price
    $precioTotalPagar = round($precioUnitario - $totalDiscount, 2);
    
    // Add to total amount
    $totalAmount += $precioTotalPagar;
    
    // Set individual player amount
    $importe->importe = $precioTotalPagar;
    $importe->restante = 0;
    $importe->importeInscripcion = $precioInscripcion;
    $importe->total = $precioTotalPagar;

    // ... resto del código para crear la estructura de datos del jugador ...
    
    // Accumulate totals
    $importe->amountOnline += $precioTotalPagar;
    $importe->amountPresencial += $importeUnitarioFinalPresencial;
    $importe->amountOnlineInscription += $precioInscripcion;
    $importe->amountDiscountAreBrothers += $precioDescunetHermano;
    $importe->amountSinglePaymentDiscount += $precioDescunetPagoUnico;
    $importe->amountDiscountIsMember += $precioDescunetEsSocio;
}

// Set the total amount for all players
$importe->total = $totalAmount;
 ```

## Mejoras Implementadas
1. Detección de hermanos mejorada :
   
   - Se determina una sola vez si los jugadores son hermanos
   - Se aplica el mismo estado a todos los jugadores en la inscripción
2. Cálculo de descuentos optimizado :
   
   - Orden consistente para aplicar descuentos
   - Redondeo de valores para evitar problemas con decimales
3. Cálculo correcto del importe total :
   
   - Acumulador para el importe total de todos los jugadores
   - Suma correcta de importes individuales con sus descuentos
4. Estructura de datos mejorada :
   
   - Información detallada para cada jugador
   - Inclusión de todos los descuentos aplicados
5. Consistencia en los cálculos :
   
   - Valores constantes para todos los jugadores en una misma inscripción
   - Evita recálculos innecesarios
## Resolución de Problemas
Estos cambios resuelven:

- Importes restantes negativos
- Cálculos incorrectos con múltiples hermanos
- Inconsistencias en la aplicación de descuentos


