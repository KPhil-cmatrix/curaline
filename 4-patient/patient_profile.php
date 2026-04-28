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
    patient_id,
    first_name,
    last_name,
    sex,
    date_of_birth,
    phone_number,
    email,
    parish_of_residence,
    emergency_contact_name,
    emergency_contact_phone,
    emergency_contact_relationship,
    has_allergies,
    allergy_details
  from patient_info
  where patient_id = '$patient_id'
  limit 1
";

$result = mysqli_query($conn, $sql);
$patient = ($result && mysqli_num_rows($result) === 1) ? mysqli_fetch_assoc($result) : null;

function h($v) {
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Curaline – My Profile</title>
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

      <!-- My Appointments -->
      <a href="patient_appointments.php"
        class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-all duration-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M8 7V3m8 4V3m-9 8h10m-11 8h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
        <span>My Appointments</span>
      </a>

      <!-- My Profile ACTIVE -->
      <a href="patient_profile.php"
        class="flex items-center gap-3 px-4 py-3 rounded-xl bg-[#3EDCDE] text-[#2F5395] font-medium shadow-md transition-all duration-200">
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
        <h1 class="text-2xl font-bold text-[#2F5395]">My Profile</h1>
        <p class="text-sm text-gray-500 mt-1">View your personal and medical information</p>
      </div>

      <div class="flex items-center gap-3">
        <span class="text-sm text-[#9FA2B2]">
          ID: <?= h($_SESSION['user_id']) ?>
        </span>
        <div class="w-10 h-10 rounded-full bg-[#3EDCDE] flex items-center justify-center text-white font-bold">
          <?= strtoupper(substr($_SESSION['first_name'], 0, 1)) ?>
        </div>
      </div>
    </header>

    <!-- PROFILE CARD -->
    <section class="app-card p-6">

      <h2 class="text-xl font-semibold text-[#2F5395] mb-6">
        Patient Information
      </h2>

      <?php if (!$patient): ?>
        <p class="text-gray-400">Profile not found.</p>
      <?php else: ?>

        <div class="grid md:grid-cols-2 gap-6">

          <div>
            <p class="text-sm text-gray-400">Full Name</p>
            <p class="font-semibold text-lg">
              <?= h($patient['first_name']) ?> <?= h($patient['last_name']) ?>
            </p>
          </div>

          <div>
            <p class="text-sm text-gray-400">Sex</p>
            <p class="font-semibold"><?= h($patient['sex']) ?></p>
          </div>

          <div>
            <p class="text-sm text-gray-400">Date of Birth</p>
            <p class="font-semibold"><?= h($patient['date_of_birth']) ?></p>
          </div>

          <div>
            <p class="text-sm text-gray-400">Parish of Residence</p>
            <p class="font-semibold"><?= h($patient['parish_of_residence']) ?></p>
          </div>

          <div>
            <p class="text-sm text-gray-400">Phone Number</p>
            <p class="font-semibold"><?= h($patient['phone_number']) ?></p>
          </div>

          <div>
            <p class="text-sm text-gray-400">Email</p>
            <p class="font-semibold"><?= h($patient['email']) ?></p>
          </div>

          <div class="md:col-span-2">
            <p class="text-sm text-gray-400">Allergies</p>
            <p class="font-semibold">
              <?= ((int)$patient['has_allergies'] === 1) ? "Yes" : "No" ?>
              <?php if (!empty($patient['allergy_details'])): ?>
                — <?= h($patient['allergy_details']) ?>
              <?php endif; ?>
            </p>
          </div>

          <div class="md:col-span-2">
            <p class="text-sm text-gray-400">Emergency Contact</p>
            <p class="font-semibold">
              <?= h($patient['emergency_contact_name']) ?>
              (<?= h($patient['emergency_contact_relationship']) ?>) —
              <?= h($patient['emergency_contact_phone']) ?>
            </p>
          </div>

        </div>

      <?php endif; ?>

    </section>

  </div>
  <?php include __DIR__ . '/../1-assets/chatbot-widget.php' ?>
  <script src="../1-assets/js/notifications.js"></script>
</body>
</html>