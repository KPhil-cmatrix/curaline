<?php

/*
- System Name: Curaline Clinic Appointment and Patient Management System (Curaline)
- Developers: Khalia Phillips, Havon James, and Tarik Wilson
- Version: V2.2
- Version Date: Dec 15, 2025
- Purpose of File: Dasboard file that displasy general data for the system
*/

// We block access if the user is not logged in or not an admin, we require admin privildges from the admins page
require __DIR__ . '/../3-sessions/auth_staff.php';
include __DIR__ . '/../2-backend/db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// We get the total of active patients

$patients_sql = "select count(*) as total from patient_info where is_active=1";
$patients_result = mysqli_query($conn, $patients_sql);
$patients_data = mysqli_fetch_assoc($patients_result);
$active_patients = $patients_data['total'];

// We get the appointments for the day
$today = date('Y-m-d');
$app_sql = "select count(*) as total from appointments where date(scheduled_datetime) = '$today'";
$app_result = mysqli_query($conn, $app_sql);
$appointments_today = mysqli_fetch_assoc($app_result)['total'];

// We get the current total appointments

$app_count_sql = "select count(*) as total from appointments";
$app_count_result = mysqli_query($conn, $app_count_sql);
$app_count_total = mysqli_fetch_assoc($app_count_result)['total'];

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Curaline Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../1-assets/ui.css">
  </head>

  <body class="flex min-h-screen bg-gradient-to-br from-[#EEF3FA] to-[#C9D8F0] text-gray-800">
    <aside class="w-64 bg-gradient-to-b from-[#2F5395] to-[#26457C] text-white flex flex-col shadow-xl">
  
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

    <!-- Main content -->
    <div class="flex-1 p-6 space-y-6 max-w-6xl">
      <!-- Top bar -->
      <header class="app-card p-6 flex items-center justify-between mb-6">

        <!--CODE BORDER-->
        <div>
          <h1 class="text-2x1 font-bold text-[#2f5385]">Dasboard</h1>
          <p class="text-sm text-gray-500 mt-1">
            Welcome back, <?=$_SESSION['staff_role']?>
          </p>
        </div>
        <!--CODE BORDER-->
        <div class="flex items-center gap-3">
          <span class="text-sm text-[#9FA2B2]" >
            ID: <?=$_SESSION['user_id']?>
          </span>
          <div class="w-10 h-10 flex items-center justify-center rounded-full bg-[#3EDCDE] text-white font-semibold">
            <?=strtoupper(substr($_SESSION['staff_role'], 0 ,1))?>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="p-6 flex-1">
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          
          <!-- Patients -->
          <div class="app-card p-6 flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">Active Patients</p>
              <h2 class="text-3xl font-bold text-[#2F5395] mt-2"><?php echo $active_patients ?></h2>
              <p class="text-xs text-gray-400 mt-2">Currently registered and active</p>
            </div>

            <div class="w-12 h-12 rounded-full bg-[#3EDCDE]/20 flex items-center justify-center shrink-0 text-[#2F5395]">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M16 14a4 4 0 10-8 0m8 0a4 4 0 10-8 0m8 0v1a2 2 0 002 2h1m-11-3v1a2 2 0 01-2 2H4" />
              </svg>
            </div>
          </div>

          <!-- Appointments Today -->
          <div class="app-card p-6 flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">Appointments Today</p>
              <h2 class="text-3xl font-bold text-[#2F5395] mt-2"><?php echo $appointments_today ?></h2>
              <p class="text-xs text-gray-400 mt-2">Scheduled for today</p>
            </div>

            <div class="w-12 h-12 rounded-full bg-[#3EDCDE]/20 flex items-center justify-center shrink-0 text-[#2F5395]">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>

          <!-- Total Appointments -->
          <div class="app-card p-6 flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">Total Appointments</p>
              <h2 class="text-3xl font-bold text-[#2F5395] mt-2"><?php echo $app_count_total ?></h2>
              <p class="text-xs text-gray-400 mt-2">All appointments in the system</p>
            </div>

            <div class="w-12 h-12 rounded-full bg-[#3EDCDE]/20 flex items-center justify-center shrink-0">
              <div class="w-12 h-12 rounded-full bg-[#3EDCDE]/20 flex items-center justify-center shrink-0 text-[#2F5395]">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
          </div>

        </div>
      </main>
    </div>
  </body>
</html>
