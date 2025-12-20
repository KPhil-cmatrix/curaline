<?php

// First we check if there's an exisiting session and if not we create the session

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}


// We block access if the user is not logged in or not an admin
if (
  !isset($_SESSION['logged_in']) ||
  $_SESSION['logged_in'] !== true ||
  $_SESSION['staff_role'] !== 'Admin'
) {
  header("Location: login.php?role=admin");
  exit;
}
