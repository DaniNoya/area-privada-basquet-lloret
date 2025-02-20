<?php
//version 19/09/2024
class LogFile {
    private $logDirectory;

    public function __construct($logDirectory){

        if ($logDirectory==NULL or $logDirectory==""){

            $config=require_once __DIR__.'/../config/.logfile';
            $logDirectory=$config['loggerDirectory'];
        }
        $this->logDirectory = $logDirectory;

        // Crear el directorio de logs si no existe
        if (!is_dir($this->logDirectory)) {
            mkdir($this->logDirectory, 0777, true);
        }
    }

    private function getLogFileName() {
        // Obtener la fecha actual
        $date = date('Y-m-d');

        // Generar el nombre del archivo de log basado en la fecha
        return $this->logDirectory . '/log-' . $date . '.log';
    }

    public function logMessage($message) {
        // Obtener el nombre del archivo de log actual
        $logFile = $this->getLogFileName();

        // Registrar el mensaje en el archivo
        file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
    }
    public function logAction($level,$action,$description) {
        // Obtener el nombre del archivo de log actual
        $logFile = $this->getLogFileName();

        // Registrar el mensaje en el archivo
        file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' ." -- ". $level." -- ".$action." -- ".$description . PHP_EOL, FILE_APPEND);
    }
}
