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
</head>

<body class="flex min-h-screen bg-[#F3F6FA] text-gray-800">

  <aside class="w-64 bg-[#2F5395] text-white flex flex-col">
    <div class="p-6 border-b border-[#3EDCDE] flex justify-center">
      <img src="../1-assets/curalineWhiteLogo.png" alt="Curaline" class="h-12 w-auto">
    </div>

    <nav class="flex-1 p-4 space-y-2">
      <a href="patient_dashboard.php" class="block py-2 px-4 rounded-lg bg-[#3EDCDE] text-[#F3F6FA] text-xl transition">
        Dashboard
      </a>
      <a href="patient_appointments.php" class="block py-2 px-4 rounded-lg hover:bg-white/10 text-lg">
        My Appointments
      </a>
      <a href="patient_profile.php" class="block py-2 px-4 rounded-lg hover:bg-white/10 text-lg">
        My Profile
      </a>
    </nav>

    <div class="p-4 mt-auto">
      <a href="../3-sessions/logout.php"
        class="w-full block text-center bg-[#9FA2B2] py-2 rounded-lg font-semibold">
        Logout
      </a>
    </div>
  </aside>

  <div class="flex-1 flex flex-col">

    <header class="bg-white shadow p-4 flex justify-between items-center border-b border-[#E0E3E7]">
      <h1 class="text-2xl font-semibold text-[#2F5395]">Patient Dashboard</h1>
      <span class="text-[#9FA2B2] font-medium">
        Patient • <?= $_SESSION['user_id'] ?>
      </span>
    </header>

    <main class="flex-1 p-6 space-y-6">
      <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-xl font-semibold text-[#2F5395]">
          Welcome, <?= htmlspecialchars($_SESSION['first_name']) ?>
        </h2>
        <p class="text-[#9FA2B2] mt-1">
          View your recent appointments and patient details.
        </p>
      </div>

      <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold text-[#2F5395] mb-4">Recent Appointments</h3>

        <div class="overflow-x-auto">
          <table class="w-full border-collapse text-left">
            <thead>
              <tr>
                <th class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]">Date-Time</th>
                <th class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]">Doctor</th>
                <th class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]">Service</th>
                <th class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                  <tr>
                    <td class="p-3 border-b border-[#E0E3E7]"><?= $row['scheduled_datetime'] ?></td>
                    <td class="p-3 border-b border-[#E0E3E7]"><?= $row['doctor_first_name'] ?> <?= $row['doctor_last_name'] ?></td>
                    <td class="p-3 border-b border-[#E0E3E7]"><?= $row['dental_service_type'] ?></td>
                    <td class="p-3 border-b border-[#E0E3E7]"><?= $row['status'] ?></td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td class="p-3 text-[#9FA2B2]" colspan="4">No appointments found.</td>
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