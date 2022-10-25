<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Hello from Database";

$server = "<your_server_here>";
$database = "<your_database_here>";
$username = "<your_username_here>";
$password = "<your_password_here>";

// PHP Data Objects(PDO) Sample Code:
try {
    $conn = new PDO("sqlsrv:server = tcp:".$server.".database.windows.net,1433; Database = ".$database, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e) {
    print_r($e);
}
catch (Exception $error) {
    print_r($error);
}

// SQL Server Extension Sample Code:
// try {
//     $connectionInfo = array("UID" => $username, "pwd" => $password, "Database" => $database, "LoginTimeout" => 30, "Encrypt" => 1, "TrustServerCertificate" => 0);
//     $serverName = "tcp:".$server.".database.windows.net,1433";
//     $conn = sqlsrv_connect($serverName, $connectionInfo);
//     if( $conn === false )
//     {
//         echo "SQL Server Extension code failed in connection";
//     }
// }
// catch (Exception $error) {
//     print_r($error);
// }
?>
