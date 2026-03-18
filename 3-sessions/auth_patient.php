<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (
  !isset($_SESSION['logged_in']) ||
  $_SESSION['logged_in'] !== true ||
  !isset($_SESSION['user_type']) ||
  $_SESSION['user_type'] !== 'patient'
) {
  header("Location: ../login.php?role=patient");
  exit;
}