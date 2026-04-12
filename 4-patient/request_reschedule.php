<?php
session_start();
require __DIR__ . '/../3-sessions/auth_patient.php';
include __DIR__ . '/../2-backend/db.php';

$patient_id = $_SESSION['user_id'];
$error = null;
$success = null;

if (!isset($_GET['appointment_id'])) {
    die("Invalid appointment.");
}

$appointment_id = mysqli_real_escape_string($conn, $_GET['appointment_id']);

$sql = "
  SELECT
    appointment_id,
    patient_id,
    dentist_id,
    scheduled_datetime,
    status,
    dental_service_type
  FROM appointments
  WHERE appointment_id = '$appointment_id'
  LIMIT 1
";

$result = mysqli_query($conn, $sql);
$appointment = mysqli_fetch_assoc($result);

if (!$appointment || $appointment['patient_id'] !== $patient_id) {
    die("Unauthorized access.");
}

if (!in_array($appointment['status'], ['Pending', 'Scheduled'])) {
    die("This appointment cannot be rescheduled.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requested_datetime = trim($_POST['requested_datetime'] ?? '');
    $request_note = mysqli_real_escape_string($conn, trim($_POST['request_note'] ?? ''));

    if (!$requested_datetime) {
        $error = "Please select a new date and time.";
    } else {
        $check_sql = "
          SELECT appointment_id
          FROM appointments
          WHERE dentist_id = '{$appointment['dentist_id']}'
            AND scheduled_datetime = '$requested_datetime'
            AND status IN ('Pending', 'Scheduled', 'Reschedule Requested')
            AND appointment_id != '$appointment_id'
          LIMIT 1
        ";

        $check_result = mysqli_query($conn, $check_sql);

        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $error = "That time slot is not available.";
        } else {
            $update_sql = "
              UPDATE appointments
              SET
                requested_datetime = '$requested_datetime',
                request_note = '$request_note',
                status = 'Reschedule Requested'
              WHERE appointment_id = '$appointment_id'
                AND patient_id = '$patient_id'
            ";

            if (mysqli_query($conn, $update_sql)) {
                $success = "Reschedule request submitted successfully.";
            } else {
                $error = "Failed to submit request: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Curaline – Request Reschedule</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../1-assets/ui.css">
</head>
<body class="flex min-h-screen bg-gradient-to-br from-[#EEF3FA] to-[#C9D8F0] text-gray-800">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-gradient-to-b from-[#2F5395] to-[#26457C] text-white flex flex-col shadow-xl">

      <!-- Logo -->
      <div class="px-6 py-6 border-b border-white/10 flex items-center justify-center">
        <img src="../1-assets/curalineWhiteLogo.png" alt="Curaline" class="h-12 w-auto">
      </div>

      <!-- Navigation -->
      <nav class="flex-1 p-4 space-y-2">

        <!-- Dashboard -->
        <a href="patient_dashboard.php"
          class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-all duration-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 3v18h18M9 17V9m4 8V5m4 12v-4" />
          </svg>
          <span>Dashboard</span>
        </a>

        <!-- My Appointments ACTIVE -->
        <a href="patient_appointments.php"
          class="flex items-center gap-3 px-4 py-3 rounded-xl bg-[#3EDCDE] text-[#2F5395] font-medium shadow-md transition-all duration-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10m-11 8h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          <span>My Appointments</span>
        </a>

        <!-- My Profile -->
        <a href="patient_profile.php"
          class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-all duration-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M5.121 17.804A7.969 7.969 0 0112 15c2.136 0 4.07.84 5.879 2.204M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <span>My Profile</span>
        </a>

      </nav>

      <!-- Bottom -->
      <div class="p-4 border-t border-white/10 space-y-3 mt-auto">
        <div class="flex items-center gap-3 px-2">
          <div class="w-10 h-10 rounded-full bg-[#3EDCDE] flex items-center justify-center font-bold text-white shrink-0">
            <?= strtoupper(substr($_SESSION['first_name'], 0, 1)) ?>
          </div>
          <div class="text-sm min-w-0">
            <p class="font-medium leading-tight">Patient</p>
            <p class="text-xs text-white/60 leading-tight">ID: <?= $_SESSION['user_id'] ?></p>
          </div>
        </div>

        <a href="../3-sessions/logout.php"
          class="block w-full text-center bg-white/20 hover:bg-white/30 transition-all duration-200 py-2 rounded-xl text-sm font-semibold">
          Logout
        </a>
      </div>

    </aside>

  <div class="flex-1 p-6 flex justify-center items-start">
    <div class="w-full max-w-2xl app-card p-6">
      <h1 class="text-2xl font-bold text-[#2F5395] mb-2">Request Reschedule</h1>
      <p class="text-sm text-gray-500 mb-6">
        Current appointment: <?= htmlspecialchars($appointment['scheduled_datetime']) ?>
      </p>

      <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4">
          <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-[#2F5395] mb-2">Requested New Date & Time</label>
          <input
            type="datetime-local"
            name="requested_datetime"
            min="<?= date('Y-m-d\TH:i') ?>"
            class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#3EDCDE]"
            required
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-[#2F5395] mb-2">Reason (optional)</label>
          <textarea
            name="request_note"
            rows="3"
            class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#3EDCDE]"
            placeholder="Add a short note for staff"
          ></textarea>
        </div>

        <div class="flex gap-3">
          <button
            type="submit"
            class="bg-[#2F5395] text-white px-6 py-2 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition"
          >
            Submit Request
          </button>

          <a
            href="patient_appointments.php"
            class="px-6 py-2 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-100 transition"
          >
            Back
          </a>
        </div>
      </form>
    </div>
  </div>

</body>
</html>