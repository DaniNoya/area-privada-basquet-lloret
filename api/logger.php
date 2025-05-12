<?php
function writeLog($message, $type = 'INFO') {
    $logDir = __DIR__ . '/../logs';
    $logFile = $logDir . '/casal.log';
    
    // Crear directorio de logs si no existe
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp][$type] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}