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
</head>

<body class="flex min-h-screen bg-[#F3F6FA] text-gray-800">

  <aside class="w-64 bg-[#2F5395] text-white flex flex-col">
    <div class="p-6 border-b border-[#3EDCDE] flex justify-center">
      <img src="../1-assets/curalineWhiteLogo.png" class="h-12 w-auto" alt="Curaline Logo">
    </div>

    <nav class="flex-1 p-4 space-y-2">
      <a href="dashboard.php"
        class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition">
        Dashboard
      </a>

      <a href="patients.php"
        class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition">
        Patients
      </a>

      <a href="staff.php"
        class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition">
        Staff
      </a>

      <a href="appointments.php"
        class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition">
        Appointments
      </a>

      <a href="appointment_requests.php"
        class="block py-2 px-4 rounded-lg bg-[#3EDCDE] text-[#F3F6FA] transition">
        Appointment Requests
      </a>

      <?php if (isset($_SESSION['staff_role']) && $_SESSION['staff_role'] === 'Admin'): ?>
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