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
</head>

<body class="flex min-h-screen bg-[#F3F6FA] text-gray-800">

  <aside class="w-64 bg-[#2F5395] text-white flex flex-col">
    <div class="p-6 border-b border-[#3EDCDE] flex justify-center">
      <img src="../1-assets/curalineWhiteLogo.png" alt="Curaline" class="h-12 w-auto">
    </div>

    <nav class="flex-1 p-4 space-y-2">
      <a href="patient_dashboard.php" class="block py-2 px-4 rounded-lg hover:bg-white/10 text-lg">
        Dashboard
      </a>
      <a href="patient_appointments.php" class="block py-2 px-4 rounded-lg bg-[#3EDCDE] text-[#F3F6FA] text-xl transition">
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
      <h1 class="text-2xl font-semibold text-[#2F5395]">My Appointments</h1>
      <span class="text-[#9FA2B2] font-medium">
        Patient • <?= $_SESSION['user_id'] ?>
      </span>
    </header>
    
    <main class="flex-1 p-6 space-y-6">
    
            <div class="bg-white rounded-xl shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-[#2F5395] mb-4">Request an Appointment</h3>

        <?php if ($request_error): ?>
          <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4">
            <?= $request_error ?>
          </div>
        <?php endif; ?>

        <?php if ($request_success): ?>
          <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4">
            <?= $request_success ?>
          </div>
        <?php endif; ?>

        <?php
        //=====================[ FETCH ACTIVE DENTISTS ]=====================\\

        $dentists_sql = "
          select
            staff_id,
            first_name,
            last_name
          from staff_info
          where staff_role = 'Dentist'
          and is_active = 1
          order by first_name, last_name
        ";

        $dentists_result = mysqli_query($conn, $dentists_sql);
        ?>

        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm text-gray-600">Dentist</label>
            <select
              name="dentist_id"
              required
              class="w-full border rounded-lg p-2"
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
            <label class="text-sm text-gray-600">Dental Service</label>
            <input
              type="text"
              name="service"
              required
              class="w-full border rounded-lg p-2"
              placeholder="Enter dental service"
            />
          </div>

          <div>
            <input
              type="datetime-local"
              name="appointment_datetime"
              min="<?= date('Y-m-d\TH:i') ?>"
              required
              class="w-full border rounded-lg p-2"
            />
          </div>

          <div class="md:col-span-2">
            <button
              type="submit"
              name="request_appointment"
              class="bg-[#2F5395] text-white px-6 py-2 rounded-lg hover:bg-[#3EDCDE]"
            >
              Submit Request
            </button>
          </div>
        </form>
      </div>
    
      <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold text-[#2F5395] mb-4">Appointment History</h3>

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