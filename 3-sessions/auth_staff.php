<?php

/*
- System Name: Curaline Clinic Appointment and Patient Management System (Curaline)
- Developers: Khalia Phillips, Havon James, and Tarik Wilson
- Version: V2.2
- Version Date: Dec 15, 2025
- Purpose of File: Verifies staff authentication
*/

// First we check if there's an exisiting session and if not we create the session

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}


// We block access if the user is not logged in
if (
  !isset($_SESSION['logged_in']) ||
  $_SESSION['logged_in'] !== true
) {
  header("Location: login.php?role=staff");
  exit;
}
