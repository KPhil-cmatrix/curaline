<?php

/*
- System Name: Curaline Clinic Appointment and Patient Management System (Curaline)
- Developers: Khalia Phillips, Havon James, and Tarik Wilson
- Version: V2.2
- Version Date: Dec 15, 2025
- Purpose of File: Appointments page for users to schedule and edit appointments
*/

session_start();

// We block access if the user is not logged in and require general staff perms
require __DIR__ . "/sessions/auth_staff.php";



include "backend/db.php"; // Connect to the database

//======[Error and Success for handling notifications later]======\\

$error = null;
$success = null;

//===========================[ POST DATA SECTION ]===========================\\

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Here we Validate inputs, all must be present

  if (
    empty($_POST['doctor']) ||
    empty($_POST['date']) ||
    empty($_POST['time']) ||
    empty($_POST['patient']) ||
    empty($_POST['staff'])
  ) {

    $error = "All fields are required.";

  } else {

      // Get values from the form and assign to variables
      $doctor = $_POST['doctor'];       // Dentist ID
      $date   = $_POST['date'];         // Appointment date
      $time   = $_POST['time'];         // Appointment time
      $patient = $_POST['patient'];     // Patient
      $staff = $_POST['staff'];         // Created by staff member

      // Combine date + time into one datetime value
      $datetime = date("Y-m-d H:i:s", strtotime("$date $time"));

      // We do a check to ensure time submitted is valid for appointments
      $minAllowedTime = date("Y-m-d H:i:s", strtotime("+1 hour"));

      if ($datetime < $minAllowedTime) {
        $error = "Appointments must be scheduled at least 1 hour from now.";
      }

      // Now we do a little check for the availability of a time slot, ensuring for a dentist id and  a datetime
      // Also checking to make sure the status of the appointment isn't marked as "cancelled" or "Missed"

      $time_check_sql = "
      select count(*) AS total
      from appointments
      where dentist_id = '$doctor'
      and scheduled_datetime = '$datetime'
      and status NOT IN ('Cancelled', 'Missed')
      ";

      // We send through the query and fetch the results \\

      $check_result = mysqli_query($conn, $time_check_sql);
      $check_data = mysqli_fetch_assoc($check_result);

      // If it returns a value then its found an appointment that's still pending with a doctor that is on call \\
      // Dentists off call cannot have appointment so there's no need to validate that \\

      if ($check_data['total'] > 0) {
        $error = "this doctor already has an appointment at that time";
      }
      // If $error is declared and not == null then we proceed with the rest

      if (!isset($error)) {

        $sql = "INSERT INTO appointments
                (patient_id, dentist_id, booked_by_staff_id, scheduled_datetime, status, dental_service_type, booking_channel)
                VALUES ('$patient', '$doctor', '$staff', '$datetime', 'Scheduled', 'General', 'Admin')";

          if (mysqli_query($conn, $sql)) {
              $success = "Appointment booked successfully.";
          } else {
              $error = "Database error: " . mysqli_error($conn);
          }

      }
  }
}

//===========================[ GET DATA SECTION ]===========================\\

$dentists_sql = "select staff_id, first_name, last_name 
from staff_info 
where staff_role = 'Dentist'
and is_active = 1";

$patients_sql = "select patient_id, first_name, last_name
from patient_info";

$staff_sql = "select staff_id, first_name, last_name
from staff_info
where staff_role = 'Receptionist'
and is_active = 1";

/*
SQL is processed in order of "operators", things like FROM and JOIN happen before SELECT

FROM appointments runs before JOIN;
Here's the true order:

FROM -> Join -> WHERE -> GROUP BY -> SELECT -> ORDER BY
*/

$appointments_sql = "
SELECT 
  a.appointment_id,
  a.scheduled_datetime,
  a.status,
  a.dental_service_type,
  d.first_name AS doctor_first,
  d.last_name  AS doctor_last,
  p.first_name AS patient_first,
  p.last_name  AS patient_last
FROM appointments a
JOIN staff_info d ON a.dentist_id = d.staff_id
JOIN patient_info p ON a.patient_id = p.patient_id
ORDER BY a.scheduled_datetime DESC
";

//===========================[ RUNNING QUERY SECTION ]===========================\\

$dentists_result = mysqli_query($conn, $dentists_sql);
$patients_result = mysqli_query($conn, $patients_sql);
$staff_result = mysqli_query($conn, $staff_sql);
$appointments_result = mysqli_query($conn, $appointments_sql);

// Checking if data wasn't received or failed for whatever reason

if (!$dentists_result) {
  die("Failed to collect dentist info");
}

if (!$patients_result) {
  die("Failed to collect patient info");
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Curaline – Appointments</title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>

  <body class="flex min-h-screen bg-[#F3F6FA] text-gray-800">
    
    <!--------------------------------------- Sidebar --------------------------------------->

    <aside class="w-64 bg-[#2F5395] text-white flex flex-col">
      <div class="p-6 border-b border-[#3EDCDE]">
        <h1 class="text-3xl font-extrabold tracking-wide">Curaline</h1>
      </div>

      <nav class="flex-1 p-4 space-y-2">
        <a
          href="dashboard.php"
          class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition"
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
          class="block py-2 px-4 rounded-lg bg-[#3EDCDE] text-[#F3F6FA] text-xl transition"
          >Appointments</a
        >

        <!-- We limit admin section to those with the admin role only -->

        <?php if ($_SESSION['staff_role'] === 'Admin'): ?>
          <a href="admin.php"
            class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition">
            Admin
          </a>
        <?php endif; ?>
      </nav>

      <div class="p-4 mt-auto">
        <a href="sessions/logout.php"
          class="w-full block text-center bg-[#9FA2B2] py-2 rounded-lg font-semibold"
          >
        </a>
      </div>
    </aside>

    <!---------------------------------------  Main content --------------------------------------->

    <div class="flex-1 flex flex-col">

      <!-------------------------- Top bar -------------------------->

      <header
        class="bg-white shadow p-4 flex justify-between items-center border-b border-[#E0E3E7]"
      >
        <h1 class="text-2xl font-semibold text-[#2F5395]">Appointments</h1>
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

      <!-------------------------- Appointment content -------------------------->

      <main class="flex-1 p-6 space-y-6">

      <?php if (isset($_GET['deleted'])) { ?>
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
          Appointment cancelled successfully.
        </div>
      <?php } ?>


      <?php if (!empty($error)) { ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
        <?php echo $error; ?>
      </div>
      <?php } ?>

      <?php if (!empty($success)) { ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
          <?php echo $success; ?>
        </div>
      <?php } ?>


        <!-------------------------- Book Appointment -------------------------->

        <div class="bg-white rounded-xl shadow p-6">
          <h2 class="text-lg font-semibold text-[#2F5395] mb-4">
            Book a New Appointment
          </h2>
          <form method="POST" class="space-y-4">

            <!-------------------------- Select Doctor -------------------------->

            <div>
              <label class="block text-sm font-medium mb-1 text-[#2F5395]"
                >Select Doctor</label
              >

              <!-- Here we use name types "name", "date" and "time" to tell PHP what we're sending through to be processed -->

              <select
                name="doctor" class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#3EDCDE]"
              >
                <?php while ($row = mysqli_fetch_assoc($dentists_result)) { ?>
                  <option value= "<?php  echo $row['staff_id']; ?>">
                    <?php echo $row["staff_id"]."-".$row['first_name'] . " " . $row['last_name']; ?>
                </option>
                <?php } ?>
              </select>
            </div>

            <!-------------------------- Select Date -------------------------->

            <div>
              <label class="block text-sm font-medium mb-1 text-[#2F5395]"
                >Date</label
              >
              <input
                name="date"
                type="date"
                class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#3EDCDE]"
              />
            </div>

            <!--------------------------- Patient ID --------------------------->

            <div>
              <label class="block text-sm font-medium mb-1 text-[#2F5395]"
                >Patient ID</label
              >
              <select name="patient" class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#3EDCDE]">
                <?php while ($row = mysqli_fetch_assoc($patients_result)) {?>
                  <option value="<?php echo $row["patient_id"];?>">
                    <?php echo $row["patient_id"] ."-". $row["first_name"] ." ". $row["last_name"];?>
                  </option>
                <?php } ?>
              </select>
            </div>

            <!--------------------------- Select Time --------------------------->

            <div>
              <label class="block text-sm font-medium mb-1 text-[#2F5395]"
                >Time</label
              >
              <input
                name="time"
                type="time"
                class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#3EDCDE]"
              />

            </div>

             <!--------------------------- Created By --------------------------->

            <div>
              <label class="block text-sm font-medium mb-1 text-[#2F5395]"
                >Appoitnment Created By</label
              >
              <select
                name="staff" class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#3EDCDE]"
              >
                <?php while ($row = mysqli_fetch_assoc($staff_result)) { ?>
                  <option value= "<?php  echo $row['staff_id']; ?>">
                    <?php echo $row["staff_id"]."-".$row['first_name'] . " " . $row['last_name']; ?>
                </option>
                <?php } ?>
              </select>
            </div>

            <!--------------------------- Book Appointment Button --------------------------->

            <button
              type="submit"
              class="bg-[#2F5395] text-white px-4 py-2 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition font-medium"
            >
              Book Appointment
            </button>
          </form>
        </div>

        <!--------------------------- Section: Upcoming Appointments --------------------------->
        <div class="bg-white rounded-xl shadow p-6">
          <h2 class="text-lg font-semibold text-[#2F5395] mb-4">
            Upcoming Appointments
          </h2>
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-[#8FBFE0]">
                <th class="py-2 text-[#2F5395]">Doctor</th>
                <th class="py-2 text-[#2F5395]">Patient</th>
                <th class="py-2 text-[#2F5395]">Date-Time</th>
                <th class="py-2 text-[#2F5395]">Status</th>
                <th class="py-2 text-[#2F5395]">Actions</th>
              </tr>
            </thead>
            <tbody>

                <!--------- Here we populate the table with the appointment infromation --------->
              <?php while ($row = mysqli_fetch_assoc($appointments_result)) { ?>
                <tr class="border-b">
                  <td>
                    <?php echo $row['doctor_first'] . " " . $row['doctor_last']; ?>
                  </td>
                  <td>
                    <?php echo $row['patient_first'] . " " . $row['patient_last']; ?>
                  </td>
                  <td>
                    <?php echo $row['scheduled_datetime']; ?>
                  </td>
                  <td>
                    <?php echo $row['status']?>
                  </td>
                  <td>
                    <a 
                      href="edit_appointment.php?appointment_id=<?php echo $row['appointment_id']; ?>"
                      class="px-4 py-2 rounded-lg text-blue-600 hover:underline font-medium"
                    >
                      Edit
                    </a>
                  </td>
                </tr>
              <?php } ?>


            </tbody>
          </table>
        </div>
      </main>
    </div>
  </body>
</html>