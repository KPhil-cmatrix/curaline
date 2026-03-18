<?php

/*
- System Name: Curaline Clinic Appointment and Patient Management System (Curaline)
- Developers: Khalia Phillips, Havon James, and Tarik Wilson
- Version: V2.2
- Version Date: Dec 15, 2025
- Purpose of File: Ends session for logged in user
*/

session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect back to login select or staff login
header("Location: ../login_Select.php");
exit;
