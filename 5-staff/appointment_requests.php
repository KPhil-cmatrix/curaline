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

require __DIR__ . '/../3-sessions/auth_staff.php';

//=====================[ DATABASE ACCESS ]=====================\\

include __DIR__ . '/../2-backend/db.php';

//=====================[ REQUEST VARIABLES ]=====================\\

$error = null;
$success = null;

//=====================[ APPROVE / DENY HANDLER ]=====================\\

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $appointment_id = trim($_POST['appointment_id'] ?? '');
  $action = trim($_POST['action'] ?? '');

  if (!$appointment_id || !$action) {
    $error = "Invalid request action.";
  }
  else {

    //=====================[ APPROVE REQUEST ]=====================\\

    if ($action === 'approve') {

      $update_sql = "
        update appointments
        set status = 'Scheduled'
        where appointment_id = '$appointment_id'
        and status = 'Pending'
      ";

      if (mysqli_query($conn, $update_sql)) {
        $success = "Appointment request approved.";
      }
      else {
        $error = "Failed to approve request.";
      }

    }

    //=====================[ DENY REQUEST ]=====================\\

    if ($action === 'deny') {

      $update_sql = "
        update appointments
        set status = 'Denied'
        where appointment_id = '$appointment_id'
        and status = 'Pending'
      ";

      if (mysqli_query($conn, $update_sql)) {
        $success = "Appointment request denied.";
      }
      else {
        $error = "Failed to deny request.";
      }

    }

  }

}

//=====================[ FETCH PENDING REQUESTS ]=====================\\

$sql = "
  select
    a.appointment_id,
    a.scheduled_datetime,
    a.dental_service_type,
    a.status,
    pi.first_name as patient_first_name,
    pi.last_name as patient_last_name,
    si.first_name as dentist_first_name,
    si.last_name as dentist_last_name
  from appointments a
  join patient_info pi on a.patient_id = pi.patient_id
  join staff_info si on a.dentist_id = si.staff_id
  where a.status = 'Pending'
  order by a.scheduled_datetime asc
";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Curaline – Appointment Requests</title>
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

  <div class="flex-1 flex flex-col">

    <header class="bg-white shadow p-4 flex justify-between items-center border-b border-[#E0E3E7]">
      <h1 class="text-2xl font-semibold text-[#2F5395]">Appointment Requests</h1>
      <span class="text-[#9FA2B2] font-medium">
        <?= $_SESSION['staff_role'] ?> • <?= $_SESSION['user_id'] ?>
      </span>
    </header>

    <main class="flex-1 p-6">
      <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-lg font-semibold text-[#2F5395] mb-4">Pending Requests</h2>

        <?php if ($error): ?>
          <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4">
            <?= $error ?>
          </div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4">
            <?= $success ?>
          </div>
        <?php endif; ?>

        <div class="overflow-x-auto">
          <table class="w-full border-collapse text-left">
            <thead>
              <tr>
                <th class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]">Date-Time</th>
                <th class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]">Patient</th>
                <th class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]">Dentist</th>
                <th class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]">Service</th>
                <th class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]">Status</th>
                <th class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                  <tr>
                    <td class="p-3 border-b border-[#E0E3E7]"><?= $row['scheduled_datetime'] ?></td>
                    <td class="p-3 border-b border-[#E0E3E7]"><?= $row['patient_first_name'] ?> <?= $row['patient_last_name'] ?></td>
                    <td class="p-3 border-b border-[#E0E3E7]"><?= $row['dentist_first_name'] ?> <?= $row['dentist_last_name'] ?></td>
                    <td class="p-3 border-b border-[#E0E3E7]"><?= $row['dental_service_type'] ?></td>
                    <td class="p-3 border-b border-[#E0E3E7]"><?= $row['status'] ?></td>
                    <td class="p-3 border-b border-[#E0E3E7]">
                      <div class="flex gap-2">
                        <form method="POST">
                          <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                          <input type="hidden" name="action" value="approve">
                          <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded-lg">
                            Approve
                          </button>
                        </form>

                        <form method="POST">
                          <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                          <input type="hidden" name="action" value="deny">
                          <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded-lg">
                            Deny
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td class="p-3 text-[#9FA2B2]" colspan="6">No pending requests found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </main>

  </div>

</body>
</html>