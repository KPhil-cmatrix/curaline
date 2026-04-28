<?php

/*
- System Name: Curaline Clinic Appointment and Patient Management System (Curaline)
- Developers: Khalia Phillips, Havon James, and Tarik Wilson
- Version: V3
- Version Date: Mar 14, 2026
- Purpose of File: Administration page for creating and managing staff/patients
*/

session_start();

//=====================[ ACCESS CONTROL ]=====================\\

require __DIR__ . '/../3-sessions/auth_patient.php';

//=====================[ DATABASE ACCESS ]=====================\\

include __DIR__ . '/../2-backend/db.php';

//=====================[ Notifications ]=====================\\

require __DIR__ . '/../2-backend/notifications.php';


$patient_id = $_SESSION['user_id'];

$sql = "
  select
    a.appointment_id,
    a.scheduled_datetime,
    a.requested_datetime,
    a.request_note,
    a.status,
    a.cancel_requested,
    a.dental_service_type,
    a.appointment_outcome_note,
    a.recommendations_medication,
    a.appointment_outcome_note,
    a.recommendations_medication,
    si.first_name as doctor_first_name,
    si.last_name as doctor_last_name
  from appointments a
  join staff_info si on a.dentist_id = si.staff_id
  where a.patient_id = '$patient_id'
  order by a.scheduled_datetime desc
";
$result = mysqli_query($conn, $sql);


//=====================[ APPOINTMENT RESTRICTIONS LOADER ]=====================\\

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
$patient_min_notice_hours = (int) get_setting($conn, 'patient_min_notice_hours', '24');
$max_days_ahead = (int) get_setting($conn, 'max_days_ahead', '90');

//=====================[ APPOINTMENT REQUEST HANDLER ]=====================\\

$request_error = null;
$request_success = null;

if (isset($_GET['requested'])) {
    $request_success = "Appointment request submitted. Waiting for staff approval.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_appointment'])) {

  $patient_id = $_SESSION['user_id'];

  $dentist_id = trim($_POST['dentist_id'] ?? '');
  $scheduled_datetime = trim($_POST['appointment_datetime'] ?? '');
  $service = trim($_POST['service'] ?? '');

  //=====================[ VALIDATION ]=====================\\

  if (!$dentist_id || !$scheduled_datetime || !$service) {
    $request_error = "All fields are required.";
  }
  else {

    //=====================[ BOOKING RESTRICTION CHECKS ]=====================\\

    $check_sql = "
      select appointment_id
      from appointments
      where dentist_id = '$dentist_id'
      and scheduled_datetime = '$scheduled_datetime'
      and status in ('Pending','Scheduled')
      limit 1
    ";

    $appointment_ts = strtotime($scheduled_datetime);
    $time_only = date('H:i:s', $appointment_ts);
    $day_of_week = date('N', $appointment_ts); // 1=Mon ... 7=Sun

    if ($appointment_ts === false) {
        $request_error = "Invalid appointment date and time.";
    }

    if (!$request_error && $appointment_ts <= time()) {
        $request_error = "Appointments cannot be booked in the past.";
    }

    if (!$request_error && $appointment_ts < strtotime("+{$patient_min_notice_hours} hours")) {
        $request_error = "Appointments must be booked at least {$patient_min_notice_hours} hours in advance.";
    }

    if (!$request_error && $appointment_ts > strtotime("+{$max_days_ahead} days")) {
        $request_error = "Appointments cannot be booked more than {$max_days_ahead} days in advance.";
    }

    if (!$request_error && $day_of_week == 7 && $sunday_closed === '1') {
        $request_error = "The clinic is closed on Sundays.";
    }

    if (!$request_error) {
        if ($day_of_week >= 1 && $day_of_week <= 5) {
            if ($time_only < $weekday_open || $time_only >= $weekday_close) {
                $request_error = "Appointments must be within weekday clinic hours.";
            }
        } elseif ($day_of_week == 6) {
            if ($time_only < $saturday_open || $time_only >= $saturday_close) {
                $request_error = "Appointments must be within Saturday clinic hours.";
            }
        }
    }

    if (!$request_error && $time_only >= $lunch_start && $time_only < $lunch_end) {
        $request_error = "Appointments cannot be booked during the clinic lunch break.";
    }

    //=====================[ DENTIST DOUBLE BOOKING ]=====================\\

if (!$request_error) {

    $check_result = mysqli_query($conn, $check_sql);

    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $request_error = "Selected time slot is already booked.";
    }
}

//=====================[ PATIENT DOUBLE BOOKING ]=====================\\

if (!$request_error) {

    $patient_check_sql = "
        select appointment_id
        from appointments
        where patient_id = '$patient_id'
        and scheduled_datetime = '$scheduled_datetime'
        and status in ('Pending','Scheduled')
        limit 1
    ";

    $patient_check_result = mysqli_query($conn, $patient_check_sql);

    if ($patient_check_result && mysqli_num_rows($patient_check_result) > 0) {
        $request_error = "You already have an appointment at this time.";
    }

    //=====================[ CREATE REQUEST ]=====================\\

    if (!$request_error) {

        $insert_sql = "
          insert into appointments
          (
            patient_id,
            dentist_id,
            booked_by_staff_id,
            scheduled_datetime,
            dental_service_type,
            status
          )
          values
          (
            '$patient_id',
            '$dentist_id',
            NULL,
            '$scheduled_datetime',
            '$service',
            'Pending'
          )
        ";

        if (mysqli_query($conn, $insert_sql)) {
          header("Location: patient_appointments.php?requested=1");
          exit;
        } else {
            $request_error = "Failed to submit request: " . mysqli_error($conn);
        }
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
    <title>Curaline – My Appointments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../1-assets/ui.css">
    <script src="../1-assets/js/notifications.js"></script>
  </head>

  <body class="flex min-h-screen bg-gradient-to-br from-[#EEF3FA] to-[#C9D8F0] text-gray-800">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-gradient-to-b from-[#2F5395] to-[#26457C] text-white flex flex-col shadow-xl sticky top-0 h-screen">

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

    <!-- MAIN -->
    <div class="flex-1 p-6 space-y-6 max-w-6xl">

      <!-- HEADER -->
      <header class="app-card p-6 flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-[#2F5395]">My Appointments</h1>
          <p class="text-sm text-gray-500 mt-1">Request and manage your appointments</p>
        </div>

        <div class="flex items-center gap-3">
          <span class="text-sm text-[#9FA2B2]">ID: <?= $_SESSION['user_id'] ?></span>
          <div class="w-10 h-10 rounded-full bg-[#3EDCDE] flex items-center justify-center text-white font-bold">
            <?= strtoupper(substr($_SESSION['first_name'], 0, 1)) ?>
          </div>
        </div>
      </header>

      <!-- REQUEST CARD -->
      <section class="app-card p-6">
        <h2 class="text-xl font-semibold text-[#2F5395] mb-4">Request an Appointment</h2>

        <?php if ($request_error): ?>
          <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4">
            <?= $request_error ?>
          </div>
        <?php endif; ?>

        <?php if ($request_success): ?>
          <div class="bg-green-100 text-green-700 px-4 py-2 rounded mb-4">
            <?= $request_success ?>
          </div>
        <?php endif; ?>

        <?php
          $dentists_query = "SELECT staff_id, first_name, last_name FROM staff_info WHERE staff_role = 'Dentist'";
          $dentists_result = mysqli_query($conn, $dentists_query);
        ?>

        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">

          <div>
            <label class="block text-sm text-gray-600 mb-2">Dentist</label>
            <select name="dentist_id" required
              class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#3EDCDE]">

              <option value="">Select Dentist</option>

              <?php while ($dentist = mysqli_fetch_assoc($dentists_result)): ?>
                <option value="<?= $dentist['staff_id'] ?>">
                  <?= $dentist['staff_id'] ?> - <?= $dentist['first_name'] ?> <?= $dentist['last_name'] ?>
                </option>
              <?php endwhile; ?>

            </select>
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-2">Dental Service</label>
            <input
              type="text"
              name="service"
              required
              placeholder="Enter dental service"
              class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#3EDCDE]"
            />
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-2">Date & Time</label>
            <input
              type="datetime-local"
              name="appointment_datetime"
              min="<?= date('Y-m-d\TH:i') ?>"
              required
              class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#3EDCDE]"
            />
          </div>

          <div class="md:col-span-2">
            <button
              type="submit"
              name="request_appointment"
              class="bg-[#2F5395] text-white px-6 py-2 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition-all duration-200"
            >
              Submit Request
            </button>
          </div>

        </form>
      </section>

      <!-- HISTORY -->
      <section class="app-card p-6">
        <h2 class="text-xl font-semibold text-[#2F5395] mb-4">Appointment History</h2>

        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-[#8FBFE0] text-[#2F5395]">
              <th class="py-3">Date</th>
              <th class="py-3">Doctor</th>
              <th class="py-3">Service</th>
              <th class="py-3">Status</th>
              <th class="py-3">Actions</th>
              <th class="py-3">Outcome Note</th>
              <th class="py-3">Recommendations</th>
            </tr>
          </thead>

          <tbody>
          
          <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <?php
              $has_pending_request = (
                $row['status'] === 'Reschedule Requested' ||
                !empty($row['cancel_requested'])
              );
            ?>
            <tr>
              <!-- Date -->
              <td class="p-3 border-b border-[#E0E3E7]">
                <?= htmlspecialchars($row['scheduled_datetime']) ?>
              </td>

              <!-- Doctor -->
              <td class="p-3 border-b border-[#E0E3E7]">
                <?= htmlspecialchars($row['doctor_first_name']) ?> <?= htmlspecialchars($row['doctor_last_name']) ?>
              </td>

              <!-- Service -->
              <td class="p-3 border-b border-[#E0E3E7]">
                <?= htmlspecialchars($row['dental_service_type']) ?>
              </td>

              <!-- Status + Notes -->
              <td class="p-3 border-b border-[#E0E3E7] align-top">
                <?php
                  $status = strtolower($row['status']);
                  $status_class = 'bg-gray-100 text-gray-600';

                  if ($status === 'scheduled') {
                    $status_class = 'bg-green-100 text-green-700';
                  } elseif ($status === 'pending') {
                    $status_class = 'bg-yellow-100 text-yellow-700';
                  } elseif ($status === 'cancelled' || $status === 'declined') {
                    $status_class = 'bg-red-100 text-red-700';
                  } elseif ($status === 'completed') {
                    $status_class = 'bg-blue-100 text-blue-700';
                  } elseif ($status === 'reschedule requested') {
                    $status_class = 'bg-yellow-100 text-yellow-700';
                  }
                ?>

                <!-- Status Badge -->
                <span class="px-3 py-1 rounded-full text-xs font-medium <?= $status_class ?>">
                  <?= htmlspecialchars($row['status']) ?>
                </span>

                <!-- Requested Time -->
                <?php if ($row['status'] === 'Reschedule Requested' && !empty($row['requested_datetime'])): ?>
                  <p class="text-xs text-gray-400 mt-2">
                    Requested: <?= htmlspecialchars($row['requested_datetime']) ?>
                  </p>
                <?php endif; ?>

                <!-- Notes (only for completed) -->
                <?php if ($row['status'] === 'Completed'): ?>

                  <?php if (!empty($row['appointment_outcome_note'])): ?>
                    <div class="mt-2 text-xs text-gray-600">
                      <span class="font-semibold text-[#2F5395]">Outcome:</span><br>
                      <?= nl2br(htmlspecialchars($row['appointment_outcome_note'])) ?>
                    </div>
                  <?php endif; ?>

                  <?php if (!empty($row['recommendations_medication'])): ?>
                    <div class="mt-2 text-xs text-gray-600">
                      <span class="font-semibold text-[#2F5395]">Recommendations / Medication:</span><br>
                      <?= nl2br(htmlspecialchars($row['recommendations_medication'])) ?>
                    </div>
                  <?php endif; ?>

                <?php endif; ?>

              </td>

              <td class="py-3">
                <?= !empty($row['appointment_outcome_note']) 
                    ? htmlspecialchars($row['appointment_outcome_note']) 
                    : '<span class="text-gray-400">N/A</span>' ?>
              </td>

              <td class="py-3">
                <?= !empty($row['recommendations_medication']) 
                    ? htmlspecialchars($row['recommendations_medication']) 
                    : '<span class="text-gray-400">N/A</span>' ?>
              </td>

              <!-- Actions -->
              <td class="p-3 border-b border-[#E0E3E7]">
                <?php if ($row['status'] === 'Scheduled' && !$has_pending_request): ?>
                  <a
                    href="request_reschedule.php?appointment_id=<?= urlencode($row['appointment_id']) ?>"
                    class="text-blue-600 hover:underline"
                  >
                    Request Reschedule
                  </a>

                <?php elseif ($has_pending_request): ?>
                  <span class="text-gray-400 text-sm">Request Pending</span>

                <?php else: ?>
                  <span class="text-gray-400">N/A</span>
                <?php endif; ?>
              </td>

            </tr>
          <?php endwhile; ?>

          </tbody>
        </table>

      </section>

    </div>
    <?php include __DIR__ . '/../1-assets/chatbot-widget.php' ?>
    <script src="../1-assets/js/notifications.js"></script>
  </body>
</html>