<?php

/*
- System Name: Curaline Clinic Appointment and Patient Management System (Curaline)
- Developers: Khalia Phillips, Havon James, and Tarik Wilson
- Version: V3
- Version Date: Mar 14, 2026
- Purpose of File: Administration page for creating and managing staff/patients
*/

session_start();
require __DIR__ . '/../3-sessions/auth_patient.php';
include __DIR__ . '/../2-backend/db.php';

$patient_id = $_SESSION['user_id'];

$sql = "
  select
    a.scheduled_datetime,
    a.status,
    a.dental_service_type,
    si.first_name as doctor_first_name,
    si.last_name as doctor_last_name
  from appointments a
  join staff_info si on a.dentist_id = si.staff_id
  where a.patient_id = '$patient_id'
  order by a.scheduled_datetime desc
";

$result = mysqli_query($conn, $sql);

//=====================[ APPOINTMENT REQUEST HANDLER ]=====================\\

$request_error = null;
$request_success = null;

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

    //=====================[ DOUBLE BOOKING CHECK ]=====================\\

    $check_sql = "
      select appointment_id
      from appointments
      where dentist_id = '$dentist_id'
      and scheduled_datetime = '$scheduled_datetime'
      and status in ('Pending','Scheduled')
      limit 1
    ";

    $check_result = mysqli_query($conn, $check_sql);

    if ($check_result && mysqli_num_rows($check_result) > 0) {

      $request_error = "Selected time slot is already booked.";

    }
    else {

      //=====================[ CREATE REQUEST ]=====================\\

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
        $request_success = "Appointment request submitted. Waiting for staff approval.";
      }
      else {
        $request_error = "Failed to submit request: " . mysqli_error($conn);
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

        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">

          <div>
            <label class="block text-sm text-gray-600 mb-2">Dentist</label>
            <select
              name="dentist_id"
              required
              class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#3EDCDE]"
            >
              <option value="">Select Dentist</option>

              <?php if ($dentists_result && mysqli_num_rows($dentists_result) > 0): ?>
                <?php while ($dentist = mysqli_fetch_assoc($dentists_result)): ?>
                  <option value="<?= $dentist['staff_id'] ?>">
                    <?= $dentist['staff_id'] ?> - <?= $dentist['first_name'] ?> <?= $dentist['last_name'] ?>
                  </option>
                <?php endwhile; ?>
              <?php endif; ?>
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
            </tr>
          </thead>

          <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
              <tr class="border-b hover:bg-[#F8FBFF]">

                <td class="py-3"><?= $row['scheduled_datetime'] ?></td>

                <td class="py-3">
                  <?= $row['doctor_first_name'] ?> <?= $row['doctor_last_name'] ?>
                </td>

                <td class="py-3"><?= $row['dental_service_type'] ?></td>

                <td class="py-3">
                  <?php
                    $status = strtolower($row['status']);
                    $style = "bg-gray-100 text-gray-600";

                    if ($status === "pending") $style = "bg-yellow-100 text-yellow-700";
                    if ($status === "scheduled") $style = "bg-green-100 text-green-700";
                    if ($status === "completed") $style = "bg-blue-100 text-blue-700";
                    if ($status === "declined") $style = "bg-red-100 text-red-700";
                  ?>
                  <span class="px-3 py-1 rounded-full text-xs font-medium <?= $style ?>">
                    <?= ucfirst($row['status']) ?>
                  </span>
                </td>

              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>

      </section>

    </div>

  </body>
</html>