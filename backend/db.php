<?php
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
