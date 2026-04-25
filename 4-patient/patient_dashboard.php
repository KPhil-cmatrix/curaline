<?php

/*
- System Name: Curaline Clinic Appointment and Patient Management System (Curaline)
- Developers: Khalia Phillips, Havon James, and Tarik Wilson
- Version: V3
- Version Date: Mar 14, 2026
- Purpose of File: Patient dashboard page
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
    a.scheduled_datetime,
    a.status,
    a.dental_service_type,
    si.first_name as doctor_first_name,
    si.last_name as doctor_last_name
  from appointments a
  join staff_info si on a.dentist_id = si.staff_id
  where a.patient_id = '$patient_id'
  order by a.scheduled_datetime desc
  limit 5
";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Curaline – Patient Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../1-assets/ui.css">
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

      <!-- Dashboard ACTIVE -->
      <a href="patient_dashboard.php"
        class="flex items-center gap-3 px-4 py-3 rounded-xl bg-[#3EDCDE] text-[#2F5395] font-medium shadow-md transition-all duration-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 3v18h18M9 17V9m4 8V5m4 12v-4" />
        </svg>
        <span>Dashboard</span>
      </a>

      <!-- My Appointments -->
      <a href="patient_appointments.php"
        class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-all duration-200">
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

    <!--------------------------- Notifications --------------------------->
    <div class="px-4 mt-4">
      <button onclick="toggleNotifications()" class="w-full text-left bg-white/20 px-4 py-2 rounded-lg text-sm flex items-center justify-between">
        <span>🔔 Notifications</span>
        <span id="notif-count" class="hidden bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">0</span>
      </button>

      <div id="notif-box" class="mt-2 bg-white text-black rounded-lg p-3 max-h-60 overflow-y-auto shadow-lg">
        <div id="notifications-container">
          <p class="text-gray-400 text-sm">Loading...</p>
        </div>
      </div>

    </div>
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

  <!-- MAIN CONTENT -->
  <div class="flex-1 p-6 space-y-6 max-w-6xl">

    <!-- Header -->
    <header class="app-card p-6 flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-[#2F5395]">Patient Dashboard</h1>
        <p class="text-sm text-gray-500 mt-1">View your recent appointments and account information</p>
      </div>

      <div class="flex items-center gap-3">
        <span class="text-sm text-[#9FA2B2]">
          ID: <?= $_SESSION['user_id'] ?>
        </span>
        <div class="w-10 h-10 flex items-center justify-center rounded-full bg-[#3EDCDE] text-white font-semibold">
          <?= strtoupper(substr($_SESSION['first_name'], 0, 1)) ?>
        </div>
      </div>
    </header>

    <!-- Welcome Card -->
    <section class="app-card p-6">
      <h2 class="text-2xl font-bold text-[#2F5395]">
        Welcome, <?= htmlspecialchars($_SESSION['first_name']) ?>
      </h2>
      <p class="text-sm text-gray-500 mt-2">
        Review your latest appointments and keep track of your dental care.
      </p>
    </section>

    <!-- Recent Appointments -->
    <section class="app-card p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-semibold text-[#2F5395]">Recent Appointments</h3>
        <a href="patient_appointments.php" class="text-sm font-medium text-[#2F5395] hover:underline">
          View All
        </a>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left border-collapse">
          <thead>
            <tr class="border-b border-[#8FBFE0] text-[#2F5395]">
              <th class="py-3">Date & Time</th>
              <th class="py-3">Doctor</th>
              <th class="py-3">Service</th>
              <th class="py-3">Status</th>
            </tr>
          </thead>
          <tbody class="text-gray-700">
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
              <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr class="border-b border-gray-100 hover:bg-[#F8FBFF] transition-all duration-200">
                  <td class="py-3"><?= htmlspecialchars($row['scheduled_datetime']) ?></td>
                  <td class="py-3"><?= htmlspecialchars($row['doctor_first_name']) ?> <?= htmlspecialchars($row['doctor_last_name']) ?></td>
                  <td class="py-3"><?= htmlspecialchars($row['dental_service_type']) ?></td>
                  <td class="py-3">
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
                      }
                    ?>
                    <span class="px-3 py-1 rounded-full text-xs font-medium <?= $status_class ?>">
                      <?= htmlspecialchars(ucfirst($row['status'])) ?>
                    </span>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td class="py-4 text-gray-400" colspan="4">No appointments found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

  </div>
  <?php include __DIR__ . '/../1-assets/chatbot-widget.php' ?>
  <script src="../1-assets/js/notifications.js"></script>
</body>
</html>