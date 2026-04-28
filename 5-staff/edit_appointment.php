<?php

require __DIR__ . '/../3-sessions/auth_staff.php';
include __DIR__ . '/../2-backend/db.php';
require __DIR__ . '/../2-backend/notifications.php';

//===========================[ HELPERS ]===========================\\

function clean_post($conn, $key) {
  return mysqli_real_escape_string($conn, trim($_POST[$key] ?? ""));
}

function notify_patient($conn, $appointment_id, $message) {
  $appointment_id = (int)$appointment_id;

  $result = mysqli_query($conn, "
    SELECT patient_id
    FROM appointments
    WHERE appointment_id = '$appointment_id'
    LIMIT 1
  ");

  if ($result && mysqli_num_rows($result) === 1) {
    $row = mysqli_fetch_assoc($result);
    createNotification($conn, $row['patient_id'], $message);
  }
}

//===========================[ BASIC CHECK ]===========================\\

if (!isset($_GET['appointment_id'])) {
  die("Invalid appointment.");
}

$appointment_id = (int)$_GET['appointment_id'];
$error = null;
$success = null;

$appointment_outcome_note = mysqli_real_escape_string($conn, $_POST['appointment_outcome_note'] ?? '');
$recommendations_medication = mysqli_real_escape_string($conn, $_POST['recommendations_medication'] ?? '');

//===========================[ QUICK APPROVE / DECLINE ]===========================\\

if (isset($_GET['action'])) {

  if ($_GET['action'] === 'approve') {
    $sql = "UPDATE appointments SET status = 'Scheduled' WHERE appointment_id = '$appointment_id'";
  }

  if ($_GET['action'] === 'decline') {
    $sql = "UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = '$appointment_id'";
  }

  if (isset($sql) && mysqli_query($conn, $sql)) {
    notify_patient($conn, $appointment_id, "Appointment Update — check your appointments page.");
    header("Location: appointments.php");
    exit;
  }
}

//===========================[ LOAD APPOINTMENT ]===========================\\

$appointment_sql = "
  SELECT *
  FROM appointments
  WHERE appointment_id = '$appointment_id'
";

$appointment_result = mysqli_query($conn, $appointment_sql);
$appointment = mysqli_fetch_assoc($appointment_result);

if (!$appointment) {
  die("Appointment not found.");
}

//===========================[ DELETE / CANCEL ]===========================\\

if (isset($_POST['delete_appointment'])) {

  $delete_sql = "
    UPDATE appointments
    SET status = 'Cancelled'
    WHERE appointment_id = '$appointment_id'
  ";

  if (mysqli_query($conn, $delete_sql)) {
    notify_patient($conn, $appointment_id, "Appointment Cancelled — check your appointments page.");
    header("Location: appointments.php?deleted=1");
    exit;
  } else {
    $error = "Failed to delete appointment.";
  }
}

//===========================[ UPDATE APPOINTMENT ]===========================\\

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $doctor  = clean_post($conn, 'doctor');
  $date    = clean_post($conn, 'date');
  $time    = clean_post($conn, 'time');
  $status  = clean_post($conn, 'status');
  $service = clean_post($conn, 'service');

  if (empty($doctor) || empty($date) || empty($time) || empty($status) || empty($service)) {
    $error = 'All fields are required.';
  }

  if (!$error) {

    $datetime = date("Y-m-d H:i:s", strtotime("$date $time"));

    $update_sql = "
      UPDATE appointments SET
        dentist_id = '$doctor',
        scheduled_datetime = '$datetime',
        status = '$status',
        appointment_outcome_note = '$appointment_outcome_note',
        recommendations_medication = '$recommendations_medication',
        dental_service_type = '$service'
      WHERE appointment_id = '$appointment_id'
      LIMIT 1
    ";

    if (mysqli_query($conn, $update_sql)) {

      notify_patient($conn, $appointment_id, "Appointment Updated — check your appointments page.");

      $success = "Appointment updated successfully.";

      // reload updated data
      $appointment = mysqli_fetch_assoc(mysqli_query($conn, $appointment_sql));

    } else {
      $error = "Database error: " . mysqli_error($conn);
    }
  }
}

//===========================[ DROPDOWN DATA ]===========================\\

$dentists = mysqli_query($conn, "
  SELECT staff_id, first_name, last_name
  FROM staff_info
  WHERE staff_role = 'Dentist'
    AND is_active = 1
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Curaline – Edit Appointment</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../1-assets/ui.css">
</head>

<body class="flex min-h-screen bg-gradient-to-br from-[#EEF3FA] to-[#C9D8F0] text-gray-800">

  <!------------- Sidebar ------------->
  <aside class="w-64 bg-gradient-to-b from-[#2F5395] to-[#26457C] text-white flex flex-col shadow-xl sticky top-0 h-screen">
  
      <!-- Logo -->
      <div class="px-6 py-6 border-b border-white/10 flex items-center justify-center">
        <img src="../1-assets/curalineWhiteLogo.png" alt="Curaline" class="h-12 w-auto">
      </div>

      <!-- Navigation -->
      <nav class="flex-1 p-4 space-y-2">

        <!-- Dashboard -->
        <a href="dashboard.php"
          class="flex items-center gap-3 px-4 py-3 rounded-xl bg-[#3EDCDE] text-[#2F5395] font-medium shadow-md transition-all duration-200">
          
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 3v18h18M9 17V9m4 8V5m4 12v-4" />
          </svg>

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

        <!-- Staff -->
        <a href="staff.php"
          class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-all duration-200">
          
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M5.121 17.804A7.969 7.969 0 0112 15c2.136 0 4.07.84 5.879 2.204M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>

          <span>Staff</span>
        </a>

        <!-- Appointments -->
        <a href="appointments.php"
          class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-all duration-200">
          
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10m-11 8h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>

          <span>Appointments</span>
        </a>

        <!-- Admin -->
        <?php if ($_SESSION['staff_role'] === 'Admin'): ?>
          <a href="../6-admin/admin.php"
            class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-all duration-200">
            
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M11.983 5.176a2 2 0 012.034 0l1.607.93a2 2 0 001.518.18l1.78-.48a2 2 0 012.452 2.452l-.48 1.78a2 2 0 00.18 1.518l.93 1.607a2 2 0 010 2.034l-.93 1.607a2 2 0 00-.18 1.518l.48 1.78a2 2 0 01-2.452 2.452l-1.78-.48a2 2 0 00-1.518.18l-1.607.93a2 2 0 01-2.034 0l-1.607-.93a2 2 0 00-1.518-.18l-1.78.48a2 2 0 01-2.452-2.452l.48-1.78a2 2 0 00-.18-1.518l-.93-1.607a2 2 0 010-2.034l.93-1.607a2 2 0 00.18-1.518l-.48-1.78a2 2 0 012.452-2.452l1.78.48a2 2 0 001.518-.18l1.607-.93z" />
            </svg>

            <span>Admin</span>
          </a>
        <?php endif; ?>

      </nav>

      <!-- Logout -->
      <div class="p-4 border-t border-white/10 space-y-3">

      <!-- User info -->
      <div class="flex items-center gap-3 px-2">
        <div class="w-10 h-10 rounded-full bg-[#3EDCDE] flex items-center justify-center font-bold">
          <?= strtoupper(substr($_SESSION['staff_role'], 0, 1)) ?>
        </div>
        <div class="text-sm">
          <p class="font-medium"><?= $_SESSION['staff_role'] ?></p>
          <p class="text-xs text-white/60">ID: <?= $_SESSION['user_id'] ?></p>
        </div>
      </div>

      <!-- Logout -->
      <a href="../3-sessions/logout.php"
        class="block w-full text-center bg-white/20 hover:bg-white/30 transition py-2 rounded-xl text-sm font-semibold">
        Logout
      </a>

    </div>
    </aside>

  <!------------- Main Content ------------->
  <div class="flex-1 flex flex-col">

    <!------------- Top Bar ------------->
    <header class="bg-white shadow p-4 flex justify-between items-center border-b border-[#E0E3E7]">
      <h1 class="text-2xl font-semibold text-[#2F5395]">Edit Appointment</h1>
      <span class="text-[#9FA2B2] font-medium">Admin</span>
    </header>

    <!------------- Page Content ------------->
    <main class="flex-1 p-6 flex justify-center">

      <div class="w-full max-w-3xl">

      <!-- Here we have error and success messages that use the $error and $success variables in each use case for notifications -->

        <?php if (!empty($error)) { ?>
          <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
            <?php echo $error; ?>
          </div>
        <?php } ?>

        <?php if (!empty($success)) { ?>
          <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
            <?php echo $success; ?>
          </div>
        <?php } ?>

        <div class="bg-white rounded-xl shadow p-6">

          <form method="POST" class="space-y-4">

            <!-- Doctor -->
            <div>
              <label class="block text-sm font-medium mb-1 text-[#2F5395]">Doctor</label>
              <select name="doctor" class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:ring-2 focus:ring-[#3EDCDE]">
                <?php while ($d = mysqli_fetch_assoc($dentists)) { ?>
                  <option value="<?= $d['staff_id']; ?>" <?= $appointment['dentist_id'] == $d['staff_id'] ? 'selected' : ''; ?>>
                    <?= $d['first_name'] . " " . $d['last_name']; ?>
                  </option>
                <?php } ?>
              </select>
            </div>

            <!-- Date -->
            <div>
              <label class="block text-sm font-medium mb-1 text-[#2F5395]">Date</label>
              <input type="date" name="date"
                value="<?= date('Y-m-d', strtotime($appointment['scheduled_datetime'])); ?>"
                class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:ring-2 focus:ring-[#3EDCDE]" />
            </div>

            <!-- Time -->
            <div>
              <label class="block text-sm font-medium mb-1 text-[#2F5395]">Time</label>
              <input type="time" name="time"
                value="<?= date('H:i', strtotime($appointment['scheduled_datetime'])); ?>"
                class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:ring-2 focus:ring-[#3EDCDE]" />
            </div>

            <!-- Status -->
            <div>
              <label class="block text-sm font-medium mb-1 text-[#2F5395]">Status</label>
              <select name="status" class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:ring-2 focus:ring-[#3EDCDE]">
                <?php
                  $statuses = ['Pending','Scheduled','Checked-In','In-Service','Completed','Cancelled','Missed'];
                  foreach ($statuses as $s) {
                    echo "<option " . ($appointment['status'] === $s ? 'selected' : '') . ">$s</option>";
                  }
                ?>
              </select>
            </div>

            <!-- Service -->
            <div>
              <label class="block text-sm font-medium mb-1 text-[#2F5395]">Dental Service</label>
              <input type="text" name="service"
                value="<?= $appointment['dental_service_type']; ?>"
                class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:ring-2 focus:ring-[#3EDCDE]" />
            </div>

            <!--------------------------- Appointment Notes --------------------------->
            <div class="grid md:grid-cols-2 gap-6 pt-2">

              <!-- Outcome Note -->
              <div>
                <label class="block text-sm font-medium mb-1 text-[#2F5395]">
                  Appointment Outcome Note
                </label>
                <textarea
                  name="appointment_outcome_note"
                  rows="4"
                  class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:ring-2 focus:ring-[#3EDCDE]"
                  placeholder="Enter outcome notes for this appointment..."
                ><?= htmlspecialchars($appointment['appointment_outcome_note'] ?? '') ?></textarea>
              </div>

              <!-- Recommendations -->
              <div>
                <label class="block text-sm font-medium mb-1 text-[#2F5395]">
                  Recommendations / Medication
                </label>
                <textarea
                  name="recommendations_medication"
                  rows="4"
                  class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:ring-2 focus:ring-[#3EDCDE]"
                  placeholder="Enter recommendations or medication..."
                ><?= htmlspecialchars($appointment['recommendations_medication'] ?? '') ?></textarea>
              </div>

            </div>

            <!-- Actions -->
            <div class="flex gap-4 pt-4">

            <!-- Save -->
            <button
                type="submit"
                class="bg-[#2F5395] text-white px-6 py-2 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition font-medium">
                Save Changes
            </button>

            <!-- Cancel -->
            <a
                href="appointments.php"
                class="px-6 py-2 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-100 transition">
                Back
            </a>
            
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
