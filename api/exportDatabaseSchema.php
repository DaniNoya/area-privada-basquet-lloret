<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Access-Control-Allow-Methods: *");
header('Content-Type: application/json');

require_once 'dbConnection.php';

$con = returnConection();
$response = new stdClass();
$response->success = false;
$response->message = "";
$response->tables = [];

// Get database name
$dbName = mysqli_query($con, "SELECT DATABASE()")->fetch_array()[0];
if (!$dbName) {
    $response->message = "Error getting database name";
    echo json_encode($response);
    exit;
}

// Get all tables
$tablesResult = mysqli_query($con, "SHOW TABLES");
if (!$tablesResult) {
    $response->message = "Error getting tables: " . mysqli_error($con);
    echo json_encode($response);
    exit;
}

// Process each table
while ($tableRow = mysqli_fetch_array($tablesResult)) {
    $table = $tableRow[0];
    
    // Skip tables that start with "wp"
    if (strpos($table, 'wp') === 0) {
        continue;
    }
    
    $tableInfo = new stdClass();
    $tableInfo->name = $table;
    $tableInfo->columns = [];
    
    // Get table columns
    $columnsResult = mysqli_query($con, "SHOW COLUMNS FROM `$table`");
    if (!$columnsResult) {
        continue;
    }
    
    while ($column = mysqli_fetch_assoc($columnsResult)) {
        $columnInfo = [
            'name' => $column['Field'],
            'type' => $column['Type'],
            'null' => $column['Null'],
            'key' => $column['Key'],
            'default' => $column['Default'],
            'extra' => $column['Extra']
        ];
        
        $tableInfo->columns[] = $columnInfo;
    }
    
    // Get foreign keys
    $foreignKeysResult = mysqli_query($con, 
        "SELECT 
            COLUMN_NAME, 
            REFERENCED_TABLE_NAME, 
            REFERENCED_COLUMN_NAME 
        FROM 
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE 
            TABLE_SCHEMA = '$dbName' AND 
            TABLE_NAME = '$table' AND 
            REFERENCED_TABLE_NAME IS NOT NULL"
    );
    
    if ($foreignKeysResult) {
        $tableInfo->foreignKeys = [];
        while ($fk = mysqli_fetch_assoc($foreignKeysResult)) {
            $tableInfo->foreignKeys[] = [
                'column' => $fk['COLUMN_NAME'],
                'references_table' => $fk['REFERENCED_TABLE_NAME'],
                'references_column' => $fk['REFERENCED_COLUMN_NAME']
            ];
        }
    }
    
    $response->tables[] = $tableInfo;
}

$response->success = true;
$response->message = "Database schema exported successfully (excluding WordPress tables)";

echo json_encode($response, JSON_PRETTY_PRINT);
?>