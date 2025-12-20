<?php

/*
- System Name: Curaline Clinic Appointment and Patient Management System (Curaline)
- Developers: Khalia Phillips, Havon James, and Tarik Wilson
- Version: V2.2
- Version Date: Dec 15, 2025
- Purpose of File: Establishes DB connection
*/


// We make variables
$host = "gateway01.us-east-1.prod.aws.tidbcloud.com";
$port = 4000;
$user = "RqeD4qMbpsjJ5Pe.root";
$pass = "Seb1T7aBCh36e7X8";
$db   = "Curaline_System_Db";
$ssl  = __DIR__ . "/isrgrootx1.pem";

$conn = mysqli_init();

mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
mysqli_ssl_set($conn, NULL, NULL, $ssl, NULL, NULL);

if (!mysqli_real_connect(
    $conn,
    $host,
    $user,
    $pass,
    $db,
    $port,
    NULL,
    MYSQLI_CLIENT_SSL
)) {
    error_log(mysqli_connect_error());
    http_response_code(503);
    die("Database temporarily unavailable. Please try again in a moment.");
}

?>
