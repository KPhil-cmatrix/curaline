<?php

/*
- System Name: Curaline Clinic Appointment and Patient Management System (Curaline)
- Developers: Khalia Phillips, Havon James, and Tarik Wilson
- Version: V2.2
- Version Date: Dec 15, 2025
- Purpose of File: Appointments page for users to schedule and edit appointments
*/

//=====================[ ACCESS CONTROL ]=====================\\

require __DIR__ . '/../3-sessions/auth_staff.php';

//=====================[ DATABASE ACCESS ]=====================\\

include __DIR__ . '/../2-backend/db.php';

//=====================[ Notifications ]=====================\\

require __DIR__ . '/../2-backend/notifications.php';


// ===========================[ ACTION HANDLING ]=========================== \\

if (isset($_GET['action']) && isset($_GET['appointment_id'])) {

  $action = $_GET['action'];
  $appointment_id = (int) ($_GET['appointment_id'] ?? 0);

  if ($appointment_id <= 0) {
      die("Invalid appointment ID.");
  }

  // Get patient info ONCE (used for notifications)
  $user = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT p.patient_id, p.email, a.scheduled_datetime, a.requested_datetime
    FROM appointments a
    JOIN patient_info p ON a.patient_id = p.patient_id
    WHERE a.appointment_id = '$appointment_id'
    LIMIT 1
  "));

  $patient_id = $user['patient_id'] ?? null;

  // ================= APPROVE NORMAL =================
  if ($action === 'approve') {

    mysqli_query($conn, "
      UPDATE appointments
      SET status = 'Scheduled'
      WHERE appointment_id = '$appointment_id'
    ");

    if ($patient_id) {
      createNotification($conn, $patient_id, "Appointment Update — check your appointments page.");
    }

    header("Location: appointments.php");
    exit;
  }

  // ================= DECLINE NORMAL =================
  if ($action === 'decline') {

    mysqli_query($conn, "
      UPDATE appointments
      SET status = 'Cancelled'
      WHERE appointment_id = '$appointment_id'
    ");
    
    if ($patient_id) {
      createNotification($conn, $patient_id, "Appointment Update — check your appointments page.");
    }

    header("Location: appointments.php");
    exit;
  }

  // ================= APPROVE RESCHEDULE =================
  if ($action === 'approve_reschedule') {

    mysqli_query($conn, "
      UPDATE appointments
      SET
        scheduled_datetime = requested_datetime,
        requested_datetime = NULL,
        request_note = NULL,
        status = 'Scheduled'
      WHERE appointment_id = '$appointment_id'
        AND status = 'Reschedule Requested'
    ");

    if ($patient_id) {
      createNotification($conn, $patient_id, "Appointment Update — check your appointments page.");
    }

    header("Location: appointments.php");
    exit;
  }

  // ================= DECLINE RESCHEDULE =================
  if ($action === 'decline_reschedule') {

    mysqli_query($conn, "
      UPDATE appointments
      SET
        requested_datetime = NULL,
        request_note = NULL,
        status = 'Scheduled'
      WHERE appointment_id = '$appointment_id'
        AND status = 'Reschedule Requested'
    ");

    if ($patient_id) {
      createNotification($conn, $patient_id, "Appointment Update — check your appointments page.");
    }

    header("Location: appointments.php");
    exit;
  }

  // ================= APPROVE CANCEL =================
  if ($action === 'approve_cancel') {

    mysqli_query($conn, "
      UPDATE appointments
      SET
        status = 'Cancelled',
        cancel_requested = 0,
        requested_datetime = NULL,
        request_note = NULL
      WHERE appointment_id = '$appointment_id'
    ");

    if ($patient_id) {
      createNotification($conn, $patient_id, "Appointment Update — check your appointments page.");
    }

    header("Location: appointments.php");
    exit;
  }

  // ================= DECLINE CANCEL =================
  if ($action === 'decline_cancel') {

    mysqli_query($conn, "
      UPDATE appointments
      SET
        cancel_requested = 0,
        request_note = NULL
      WHERE appointment_id = '$appointment_id'
    ");

    if ($patient_id) {
      createNotification($conn, $patient_id, "Appointment Update — check your appointments page.");
    }

    header("Location: appointments.php");
    exit;
  }
}

//===========================[ VALIDATION HELPERS ]===========================\\

function clean_post($conn, $key) {
  return mysqli_real_escape_string($conn, trim($_POST[$key] ?? ""));
}


//======[Error and Success for handling notifications later]======\\

$error = null;
$success = null;

//===========================[ CLININC SETTINGS SET UP ]===========================\\

function get_setting($conn, $key, $default = null) {
    $key = mysqli_real_escape_string($conn, $key);
    $sql = "SELECT setting_value FROM clinic_settings WHERE setting_key = '$key' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['setting_value'];
    }

    return $default;
}

$weekday_open = get_setting($conn, 'weekday_open', '08:00:00');
$weekday_close = get_setting($conn, 'weekday_close', '17:00:00');
$saturday_open = get_setting($conn, 'saturday_open', '09:00:00');
$saturday_close = get_setting($conn, 'saturday_close', '14:00:00');
$sunday_closed = get_setting($conn, 'sunday_closed', '1');
$lunch_start = get_setting($conn, 'lunch_start', '12:00:00');
$lunch_end = get_setting($conn, 'lunch_end', '13:00:00');
$staff_min_notice_hours = (int) get_setting($conn, 'staff_min_notice_hours', '1');
$max_days_ahead = (int) get_setting($conn, 'max_days_ahead', '90');

//===========================[ POST DATA SECTION ]===========================\\

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Here we Validate inputs, all must be present

  if (
    empty($_POST['doctor']) ||
    empty($_POST['appointment_datetime']) ||
    empty($_POST['patient']) ||
    empty($_POST['staff'])
  ) {

    $error = "All fields are required.";

  } else {

      // Get values from the form and assign to variables
      $doctor = clean_post($conn, 'doctor');       // Dentist ID
      $appointment_datetime   = clean_post($conn, 'appointment_datetime');         // Appointment time
      $patient = clean_post($conn, 'patient');     // Patient
      $staff = clean_post($conn, 'staff');         // Created by staff member

      // Extra validation to ensure values match expected formats
      if (!preg_match('/^(DEN|NUR|STA)\d{4}$/', $doctor)) {
        $error = 'Invalid doctor selected.';
      }
      if (!preg_match('/^PAT\d{4}$/', $patient)) {
        $error = 'Invalid patient selected.';
      } 
      if (!preg_match('/^(DEN|NUR|STA)\d{4}$/', $staff)) {
        $error = 'Invalid staff member selected.';
      }
      if (!$error && strtotime($appointment_datetime) === false) {
        $error = 'Invalid appointment date and time.';
      }

      // Ensure selected IDs exist and are active where required
      if (!$error) {
        $doctor_exists = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM staff_info WHERE staff_id='$doctor' AND staff_role='Dentist' AND is_active=1"))['c'];
        if ((int)$doctor_exists !== 1) { $error = 'Selected doctor is not available.'; }
      }
      if (!$error) {
        $patient_exists = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM patient_info WHERE patient_id='$patient' AND is_active=1"))['c'];
        if ((int)$patient_exists !== 1) { $error = 'Selected patient does not exist.'; }
      }
      if (!$error) {
        $staff_exists = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM staff_info WHERE staff_id='$staff' AND staff_role='Receptionist' AND is_active=1"))['c'];
        if ((int)$staff_exists !== 1) { $error = 'Selected receptionist is not available.'; }
      }


      $datetime = date("Y-m-d H:i:s", strtotime($appointment_datetime));

      $appointment_ts = strtotime($datetime);
      $time_only = date('H:i:s', $appointment_ts);
      $day_of_week = date('N', $appointment_ts);

      // ======================[ CLINIC VALIDATION ]====================== \\

      if ($appointment_ts === false) {
        $error = "Invalid appointment date and time.";
      }

      if (!$error && $appointment_ts <= time()) {
        $error = "Appointments cannot be booked in the past.";
      }

      if (!$error && $appointment_ts < strtotime("+{$staff_min_notice_hours} hours")) {
        $error = "Appointments must be scheduled at least {$staff_min_notice_hours} hour(s) in advance.";
      }

      if (!$error && $appointment_ts > strtotime("+{$max_days_ahead} days")) {
        $error = "Appointments cannot be booked more than {$max_days_ahead} days in advance.";
      }

      if (!$error && $day_of_week == 7 && $sunday_closed === '1') {
        $error = "The clinic is closed on Sundays.";
      }

      if (!$error) {
        if ($day_of_week >= 1 && $day_of_week <= 5) {
          if ($time_only < $weekday_open || $time_only >= $weekday_close) {
            $error = "Appointments must be within weekday clinic hours.";
          }
        } elseif ($day_of_week == 6) {
          if ($time_only < $saturday_open || $time_only >= $saturday_close) {
            $error = "Appointments must be within Saturday clinic hours.";
          }
        }
      }

      if (!$error && $time_only >= $lunch_start && $time_only < $lunch_end) {
        $error = "Appointments cannot be booked during the clinic lunch break.";
      }

      // Now we do a little check for the availability of a time slot, ensuring for a dentist id and  a datetime
      // Also checking to make sure the status of the appointment isn't marked as "cancelled" or "Missed"

      $time_check_sql = "
      select count(*) AS total
      from appointments
      where dentist_id = '$doctor'
      and scheduled_datetime = '$datetime'
      and status NOT IN ('Cancelled', 'Missed')
      ";

      // We send through the query and fetch the results \\

      $check_result = mysqli_query($conn, $time_check_sql);
      $check_data = mysqli_fetch_assoc($check_result);

      // If it returns a value then its found an appointment that's still pending with a doctor that is on call \\
      // Dentists off call cannot have appointment so there's no need to validate that \\

      if (!$error && $check_data['total'] > 0) {
        $error = "This doctor already has an appointment at that time.";
      }

      // Patient double-booking check
      if (!$error) {
        $patient_time_check_sql = "
          SELECT COUNT(*) AS total
          FROM appointments
          WHERE patient_id = '$patient'
            AND scheduled_datetime = '$datetime'
            AND status NOT IN ('Cancelled', 'Missed', 'Denied')
        ";

        $patient_check_result = mysqli_query($conn, $patient_time_check_sql);
        $patient_check_data = mysqli_fetch_assoc($patient_check_result);

        if ($patient_check_data['total'] > 0) {
          $error = "This patient already has an appointment at that time.";
        }
      }

      // If $error is declared and not == null then we proceed with the rest

      if (!$error) {

      $sql = "INSERT INTO appointments
        (patient_id, dentist_id, booked_by_staff_id, scheduled_datetime, status, dental_service_type, booking_channel)
        VALUES ('$patient', '$doctor', '$staff', '$datetime', 'Scheduled', '$service', 'Admin')";
                
        if (mysqli_query($conn, $sql)) {

            $user = mysqli_fetch_assoc(mysqli_query($conn, "
                SELECT email
                FROM patient_info
                WHERE patient_id = '$patient'
                LIMIT 1
            "));

            if ($user && !empty($user['email'])) {
                send_email_notification(
                    $user['email'],
                    "Appointment Booked",
                    "Your appointment has been scheduled for {$datetime}."
                );
            }

            $success = "Appointment booked successfully.";

        } else {
            $error = "Database error: " . mysqli_error($conn);
        }

      }
  }
}

//===========================[ GET DATA SECTION ]===========================\\

$dentists_sql = "select staff_id, first_name, last_name 
from staff_info 
where staff_role = 'Dentist'
and is_active = 1";

$patients_sql = "select patient_id, first_name, last_name
from patient_info";

$staff_sql = "select staff_id, first_name, last_name
from staff_info
where staff_role = 'Receptionist'
and is_active = 1";


$scheduled_appointments_sql = "
SELECT 
  a.appointment_id,
  a.scheduled_datetime,
  a.status,
  a.dental_service_type,
  a.cancel_requested,
  d.first_name AS doctor_first,
  d.last_name  AS doctor_last,
  p.first_name AS patient_first,
  p.last_name  AS patient_last
FROM appointments a
JOIN staff_info d ON a.dentist_id = d.staff_id
JOIN patient_info p ON a.patient_id = p.patient_id
WHERE a.status = 'Scheduled'
  AND (a.cancel_requested = 0 OR a.cancel_requested IS NULL)
ORDER BY a.scheduled_datetime DESC
";

$pending_appointments_sql = "
SELECT
    a.appointment_id,
    a.scheduled_datetime,
    a.requested_datetime,
    a.request_note,
    a.cancel_requested,
    a.status,
    a.dental_service_type,
    d.first_name AS doctor_first,
    d.last_name AS doctor_last,
    p.first_name AS patient_first,
    p.last_name AS patient_last,
    a.patient_id
FROM appointments a
JOIN staff_info d ON a.dentist_id = d.staff_id
JOIN patient_info p ON a.patient_id = p.patient_id
WHERE a.status = 'Pending'
   OR a.status = 'Reschedule Requested'
   OR a.cancel_requested = 1
ORDER BY a.scheduled_datetime DESC
";

//===========================[ RUNNING QUERY SECTION ]===========================\\

$dentists_result = mysqli_query($conn, $dentists_sql);
$patients_result = mysqli_query($conn, $patients_sql);
$staff_result = mysqli_query($conn, $staff_sql);
$scheduled_appointments_result = mysqli_query($conn, $scheduled_appointments_sql);
$pending_appointments_result = mysqli_query($conn, $pending_appointments_sql);

// Checking if data wasn't received or failed for whatever reason

if (!$dentists_result) {
  die("Failed to collect dentist info");
}

if (!$patients_result) {
  die("Failed to collect patient info");
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Curaline – Appointments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../1-assets/ui.css">
  </head>

  <body class="flex min-h-screen bg-gradient-to-br from-[#EEF3FA] to-[#C9D8F0] text-gray-800">
    
    <!--------------------------------------- Sidebar --------------------------------------->

    <aside class="w-64 bg-gradient-to-b from-[#2F5395] to-[#26457C] text-white flex flex-col shadow-xl sticky top-0 h-screen">
      <div class="px-6 py-6 border-b border-white/10 flex items-center justify-center">
        <img src="../1-assets/curalineWhiteLogo.png" alt="Curaline" class="h-12 w-auto">
      </div>

      <nav class="flex-1 p-4 space-y-2">

        <a href="dashboard.php"
          class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-all duration-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M9 17V9m4 8V5m4 12v-4" /></svg>
          <span>Dashboard</span>
        </a>

        <!-- Patients -->
        <a href="patients.php"
          class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-all duration-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M16 14a4 4 0 10-8 0m8 0a4 4 0 10-8 0m8 0v1a2 2 0 002 2h1m-11-3v1a2 2 0 01-2 2H4" />
          </svg>
          <span>Patients</span>
        </a>

        <a href="staff.php"
          class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-all duration-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A7.969 7.969 0 0112 15c2.136 0 4.07.84 5.879 2.204M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
          <span>Staff</span>
        </a>

        <!-- same sidebar, but make appointments.php use: -->
        <a href="appointments.php"
          class="flex items-center gap-3 px-4 py-3 rounded-xl bg-[#3EDCDE] text-[#2F5395] font-medium shadow-md transition-all duration-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10m-11 8h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
          <span>Appointments</span>
        </a>

        <?php if ($_SESSION['staff_role'] === 'Admin'): ?>
          <a href="../6-admin/admin.php"
            class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-all duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.983 5.176a2 2 0 012.034 0l1.607.93a2 2 0 001.518.18l1.78-.48a2 2 0 012.452 2.452l-.48 1.78a2 2 0 00.18 1.518l.93 1.607a2 2 0 010 2.034l-.93 1.607a2 2 0 00-.18 1.518l.48 1.78a2 2 0 01-2.452 2.452l-1.78-.48a2 2 0 00-1.518.18l-1.607.93a2 2 0 01-2.034 0l-1.607-.93a2 2 0 00-1.518-.18l-1.78.48a2 2 0 01-2.452-2.452l.48-1.78a2 2 0 00-.18-1.518l-.93-1.607a2 2 0 010-2.034l.93-1.607a2 2 0 00.18-1.518l-.48-1.78a2 2 0 012.452-2.452l1.78.48a2 2 0 001.518-.18l1.607-.93z" /></svg>
            <span>Admin</span>
          </a>
        <?php endif; ?>

      </nav>

      <div class="p-4 border-t border-white/10 space-y-3">
        <div class="flex items-center gap-3 px-2">
          <div class="w-10 h-10 rounded-full bg-[#3EDCDE] flex items-center justify-center font-bold text-white shrink-0">
            <?= strtoupper(substr($_SESSION['staff_role'], 0, 1)) ?>
          </div>
          <div class="text-sm min-w-0">
            <p class="font-medium leading-tight"><?= $_SESSION['staff_role'] ?></p>
            <p class="text-xs text-white/60 leading-tight">ID: <?= $_SESSION['user_id'] ?></p>
          </div>
        </div>

        <a href="../3-sessions/logout.php"
          class="block w-full text-center bg-white/20 hover:bg-white/30 transition-all duration-200 py-2 rounded-xl text-sm font-semibold">
          Logout
        </a>
      </div>

    </aside>

    <!---------------------------------------  Main content --------------------------------------->

    <div class="flex-1 p-6 space-y-6 max-w-6xl">
      <!-- Top bar -->
      <header class="app-card p-6 flex items-center justify-between mb-6">

        <div>
          <h1 class="text-2x1 font-bold text-[#2f5385]">Appointments</h1>
          <p class="text-sm text-gray-500 mt-1">
            Create and manage appointments within the clinic
          </p>
        </div>

        <div class="flex items-center gap-3">
          <span class="text-sm text-[#9FA2B2]" >
            ID: <?=$_SESSION['user_id']?>
          </span>
          <div class="w-10 h-10 flex items-center justify-center rounded-full bg-[#3EDCDE] text-white font-semibold">
            <?=strtoupper(substr($_SESSION['staff_role'], 0 ,1))?>
          </div>
        </div>
      </header>

      <!-------------------------- Appointment content -------------------------->

      <main class="flex-1 p-6 space-y-6">

      <?php if (isset($_GET['deleted'])) { ?>
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
          Appointment cancelled successfully.
        </div>
      <?php } ?>


      <?php if (!empty($error)) { ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
        <?php echo $error; ?>
      </div>
      <?php } ?>

      <?php if (!empty($success)) { ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
          <?php echo $success; ?>
        </div>
      <?php } ?>


        <!-------------------------- Book Appointment -------------------------->

        <div class="app-card p-6 "> 
          <h2 class="text-lg font-semibold text-[#2F5395] mb-4">
            Book a New Appointment
          </h2>
          <form method="POST" class="space-y-4">

            <!-------------------------- Select Doctor -------------------------->

            <div>
              <label class="block text-sm font-medium mb-1 text-[#2F5395]"
                >Select Doctor</label
              >

              <!-- Here we use name types "name", "date" and "time" to tell PHP what we're sending through to be processed -->

              <select
                name="doctor" class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#3EDCDE]"
              >
                <?php while ($row = mysqli_fetch_assoc($dentists_result)) { ?>
                  <option value= "<?php  echo $row['staff_id']; ?>">
                    <?php echo $row["staff_id"]."-".$row['first_name'] . " " . $row['last_name']; ?>
                </option>
                <?php } ?>
              </select>
            </div>

            <!--------------------------- Patient ID --------------------------->

            <div>
              <label class="block text-sm font-medium mb-1 text-[#2F5395]"
                >Patient ID</label
              >
              <select name="patient" class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#3EDCDE]">
                <?php while ($row = mysqli_fetch_assoc($patients_result)) {?>
                  <option value="<?php echo $row["patient_id"];?>">
                    <?php echo $row["patient_id"] ."-". $row["first_name"] ." ". $row["last_name"];?>
                  </option>
                <?php } ?>
              </select>
            </div>

            <!-------------------------- Select Date/Time -------------------------->

            <div>
              <label class="block text-sm font-medium mb-1 text-[#2F5395]">Appointment Date & Time</label>
              <input
                name="appointment_datetime"
                type="datetime-local"
                min="<?= date('Y-m-d\TH:i') ?>"
                class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#3EDCDE]"
              />
            </div>

             <!--------------------------- Created By --------------------------->

            <div>
              <label class="block text-sm font-medium mb-1 text-[#2F5395]"
                >Appoitnment Created By</label
              >
              <select
                name="staff" class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#3EDCDE]"
              >
                <?php while ($row = mysqli_fetch_assoc($staff_result)) { ?>
                  <option value= "<?php  echo $row['staff_id']; ?>">
                    <?php echo $row["staff_id"]."-".$row['first_name'] . " " . $row['last_name']; ?>
                </option>
                <?php } ?>
              </select>
            </div>

            <!--------------------------- Book Appointment Button --------------------------->

            <button
              type="submit"
              class="bg-[#2F5395] text-white px-4 py-2 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition font-medium"
            >
              Book Appointment
            </button>
          </form>
        </div>


        <!--------------------------- Section: Pending Appointments --------------------------->
        <div class="app-card bg-white rounded-xl shadow p-6">
          <h2 class="text-lg font-semibold text-[#2F5395] mb-4">
            Pending Appointments & Requests
          </h2>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-[#8FBFE0]">
                  <th class="py-2 text-[#2F5395]">Doctor</th>
                  <th class="py-2 text-[#2F5395]">Patient</th>
                  <th class="py-2 text-[#2F5395]">Current Date-Time</th>
                  <th class="py-2 text-[#2F5395]">Request Details</th>
                  <th class="py-2 text-[#2F5395]">Status</th>
                  <th class="py-2 text-[#2F5395]">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($row = mysqli_fetch_assoc($pending_appointments_result)) { ?>
                  <?php
                    $is_reschedule = ($row['status'] === 'Reschedule Requested');
                    $is_cancel = !empty($row['cancel_requested']);
                    $is_normal_pending = ($row['status'] === 'Pending' && !$is_cancel);
                  ?>
                  <tr class="border-b align-top">
                    <td class="py-3 pr-6">
                      <?= htmlspecialchars($row['doctor_first'] . " " . $row['doctor_last']) ?>
                    </td>

                    <td class="py-3 pr-6">
                      <?= htmlspecialchars($row['patient_first'] . " " . $row['patient_last']) ?>
                    </td>

                    <td class="py-3 pr-6 min-w-[220px]">
                      <?= htmlspecialchars($row['scheduled_datetime']) ?>
                    </td>

                    <td class="py-3 pr-6 min-w-[260px]">
                      <?php if ($is_reschedule && !empty($row['requested_datetime'])): ?>
                        <p class="text-sm text-gray-700">
                          <span class="font-medium">Requested Date:</span>
                          <?= htmlspecialchars($row['requested_datetime']) ?>
                        </p>
                      <?php elseif ($is_cancel): ?>
                        <p class="text-sm text-gray-700 font-medium">
                          Patient requested cancellation.
                        </p>
                      <?php else: ?>
                        <p class="text-sm text-gray-500">
                          No additional request details.
                        </p>
                      <?php endif; ?>

                      <?php if (!empty($row['request_note'])): ?>
                        <p class="text-sm text-gray-600 mt-2">
                          <span class="font-medium">Note:</span>
                          <?= nl2br(htmlspecialchars($row['request_note'])) ?>
                        </p>
                      <?php endif; ?>
                    </td>

                    <td class="py-3 pr-6 min-w-[180px]">
                      <?php if ($is_reschedule): ?>
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded text-sm">
                          Reschedule Requested
                        </span>
                      <?php elseif ($is_cancel): ?>
                        <span class="px-3 py-1 bg-red-100 text-red-700 rounded text-sm">
                          Cancellation Requested
                        </span>
                      <?php else: ?>
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded text-sm">
                          <?= htmlspecialchars($row['status']) ?>
                        </span>
                      <?php endif; ?>
                    </td>

                    <td class="py-3 min-w-[220px]">
                      <div class="flex gap-2 flex-wrap">
                        <?php if ($is_reschedule): ?>
                          <a
                            href="appointments.php?appointment_id=<?= urlencode($row['appointment_id']) ?>&action=approve_reschedule"
                            class="px-3 py-1 bg-[#3EDCDE] text-[#2F5395] rounded text-sm hover:opacity-90"
                          >
                            Approve
                          </a>

                          <a
                            href="appointments.php?appointment_id=<?= urlencode($row['appointment_id']) ?>&action=decline_reschedule"
                            class="px-3 py-1 bg-[#9FA2B2] text-white rounded text-sm hover:opacity-90"
                          >
                            Decline
                          </a>

                        <?php elseif ($is_cancel): ?>
                          <a
                            href="appointments.php?appointment_id=<?= urlencode($row['appointment_id']) ?>&action=approve_cancel"
                            class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:opacity-90"
                          >
                            Approve Cancel
                          </a>

                          <a
                            href="appointments.php?appointment_id=<?= urlencode($row['appointment_id']) ?>&action=decline_cancel"
                            class="px-3 py-1 bg-[#9FA2B2] text-white rounded text-sm hover:opacity-90"
                          >
                            Decline Cancel
                          </a>

                        <?php elseif ($is_normal_pending): ?>
                          <a
                            href="appointments.php?appointment_id=<?= urlencode($row['appointment_id']) ?>&action=approve"
                            class="px-3 py-1 bg-[#3EDCDE] text-[#2F5395] rounded text-sm hover:opacity-90"
                          >
                            Approve
                          </a>

                          <a
                            href="appointments.php?appointment_id=<?= urlencode($row['appointment_id']) ?>&action=decline"
                            class="px-3 py-1 bg-[#9FA2B2] text-white rounded text-sm hover:opacity-90"
                          >
                            Decline
                          </a>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>

        <!--------------------------- Section: Upcoming Appointments --------------------------->
        <div class="app-card bg-white rounded-xl shadow p-6">
          <h2 class="text-lg font-semibold text-[#2F5395] mb-4">
            Upcoming Appointments
          </h2>
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-[#8FBFE0]">
                <th class="py-2 text-[#2F5395]">Doctor</th>
                <th class="py-2 text-[#2F5395]">Patient</th>
                <th class="py-2 text-[#2F5395]">Date-Time</th>
                <th class="py-2 text-[#2F5395]">Status</th>
                <th class="py-2 text-[#2F5395]">Actions</th>
              </tr>
            </thead>
            <tbody>

                <!--------- Here we populate the table with the appointment infromation --------->
              <?php while ($row = mysqli_fetch_assoc($scheduled_appointments_result)) { ?>
                <tr class="border-b">
                  <td>
                    <?php echo $row['doctor_first'] . " " . $row['doctor_last']; ?>
                  </td>
                  <td>
                    <?php echo $row['patient_first'] . " " . $row['patient_last']; ?>
                  </td>
                  <td>
                    <?php echo $row['scheduled_datetime']; ?>
                  </td>
                  <td>
                    <?php echo $row['status']?>
                  </td>
                  <td>
                    <a 
                      href="edit_appointment.php?appointment_id=<?php echo $row['appointment_id']; ?>"
                      class="px-4 py-2 rounded-lg text-blue-600 hover:underline font-medium"
                    >
                      Edit
                    </a>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>


      </main>
    </div>
  </body>
</html>