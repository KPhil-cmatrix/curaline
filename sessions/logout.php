<?php
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect back to login select or staff login
header("Location: ../login.php?role=staff");
exit;
