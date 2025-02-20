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
       API_URL: 'http://localhost/test_basquetlloret/areaprivada/api'
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

     npm install
     ```

4. **Iniciar el servidor de desarrollo**:
Para iniciar el servidor de desarrollo y levantar la aplicación, ejecuta el siguiente comando:

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


