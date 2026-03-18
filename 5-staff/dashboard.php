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

error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__ . '/../2-backend/db.php'; // Connect to the database

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
  </head>

  <body class="flex min-h-screen bg-[#F3F6FA] text-gray-800">
    <aside class="w-64 bg-[#2F5395] text-white flex flex-col">
      <div class="p-6 border-b border-[#3EDCDE] flex justify-center">
        <img src="../1-assets/curalineWhiteLogo.png" alt="Curaline" class="h-12 w-auto">
      </div>

      <nav class="flex-1 p-4 space-y-2">
        <a
          href="dashboard.php"
          class="block py-2 px-4 rounded-lg bg-[#3EDCDE] text-[#F3F6FA] transition text-xl"
          >Dashboard</a
        >
        <a
          href="patients.php"
          class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition"
          >Patients</a
        >
        <a
          href="staff.php"
          class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition"
          >Staff</a
        >

        <a
          href="appointments.php"
          class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition"
          >Appointments</a
        >

        <!-- We limit admin section to those with the admin role only -->

        <?php if ($_SESSION['staff_role'] === 'Admin'): ?>
          <a href="../6-admin/admin.php"
            class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition">
            Admin
          </a>
        <?php endif; ?>
      </nav>

      <div class="p-4 mt-auto">
        <a href="../3-sessions/logout.php"
          class="w-full block text-center bg-[#9FA2B2] py-2 rounded-lg font-semibold">
          Logout
        </a>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col">
      <!-- Top bar -->
      <header
        class="bg-white shadow p-4 flex justify-between items-center border-b border-[#E0E3E7]"
      >
        <h1 class="text-xl font-semibold text-[#2F5395]">Dashboard</h1>
        <div class="flex items-center gap-3">
          <span class="text-[#9FA2B2] font-medium">
            <?= $_SESSION['staff_role'] ?> • <?= $_SESSION['user_id'] ?>
          </span>
          <img
            src="https://placehold.co/40x40"
            alt="avatar"
            class="rounded-full border-2 border-[#3EDCDE]"
          />
        </div>
      </header>

      <!-- Page content -->
      <main class="p-6 flex-1">
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Patients -->
          <div
            class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition"
          >
            <h2 class="text-lg font-semibold text-[#2F5395] mb-2">Patients</h2>
            <p class="text-[#9FA2B2]">
              Active patients:
              <span class="font-bold text-[#3EDCDE]"
                ><?php echo $active_patients?></span
              >
            </p>
          </div>

          <!-- Appointments -->
          <div
            class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition"
          >
            <h2 class="text-lg font-semibold text-[#2F5395] mb-2">
              Appointments
            </h2>
            <p class="text-[#9FA2B2]">
              Upcoming today:
              <span class="font-bold text-[#3EDCDE]"
                ><?php echo $appointments_today?></span
              >
            </p>
          </div>

          <!-- All Appointments -->
          <div
            class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition"
          >
            <h2 class="text-lg font-semibold text-[#2F5395] mb-2">
              Number of Total Appointments
            </h2>
            <p class="text-[#9FA2B2]">
              Unread alerts:
              <span class="font-bold text-[#3EDCDE]"
                ><?php echo $app_count_total?></span
              >
            </p>
          </div>
        </div>
      </main>
    </div>
  </body>
</html>
