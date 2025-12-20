<?php
include "backend/db.php";

// We block access if the user is not logged in and require general staff perms
require __DIR__ . "/sessions/auth_staff.php";

// =================[ BASIC CHECK ]=================\\
if (!isset($_GET['appointment_id'])) {
  die("Invalid appointment.");
}

$appointment_id = $_GET['appointment_id'];
$error = null;
$success = null;

// =================[ LOAD APPOINTMENT ]=================\\

$appointment_sql = "
  SELECT *
  FROM appointments
  WHERE appointment_id = '$appointment_id'
";

$appointment_result = mysqli_query($conn, $appointment_sql);
$appointment = mysqli_fetch_assoc($appointment_result);

if (!$appointment) {
  die("Appointment not found.");

}
// =================[ HANDLE DELETE ]=================\\

if (isset($_POST['delete_appointment'])) {

  $delete_sql = "
    UPDATE appointments
    SET status = 'Cancelled'
    WHERE appointment_id = '$appointment_id'
  ";

  if (mysqli_query($conn, $delete_sql)) {
    header("Location: appointments.php?deleted=1");
    exit;
  } else {
    $error = "Failed to delete appointment.";
  }
}


// =================[ HANDLE DB UPDATE ]=================\\

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // We collect updated values from the form \\
  $doctor  = $_POST['doctor'];
  $date    = $_POST['date'];
  $time    = $_POST['time'];
  $status  = $_POST['status'];
  $service = $_POST['service'];

  // Combine date + time into a proper datetime \\
  $datetime = date("Y-m-d H:i:s", strtotime("$date $time"));

  // We get current datetime from the database
  $current_datetime = $appointment['scheduled_datetime'];

  // Checking if the datetime has changed and we classify it as a rescheduled appointment
  $is_reschedule = ($datetime !== $current_datetime);

  // We check all the appointments that are to be held ofr restrictions
  $futurestatuses = ['Scheduled', 'Checked-In', 'In-Service'];

  // Minimum allowed booking time (1 hour from now) Along with is reschedule\\
  if ($is_reschedule && in_array($status, $futurestatuses, true)) {
    $minAllowedTime = date("Y-m-d H:i:s", strtotime("+1 hour"));
    if ($datetime < $minAllowedTime) {
      $error = "Appointments must be at least 1 hour from now.";
    }
  }

  // =================[ DOUBLE BOOKING CHECK ]=================\\
  // We ensure the same dentist cannot be booked at the same time \\
  // We also exclude the current appointment being edited \\
  if (!$error & $is_reschedule) {

    $time_check_sql = "
      SELECT COUNT(*) AS total
      FROM appointments
      WHERE dentist_id = '$doctor'
        AND scheduled_datetime = '$datetime'
        AND appointment_id != '$appointment_id'
        AND status NOT IN ('Cancelled', 'Missed')
    ";

    $check = mysqli_fetch_assoc(mysqli_query($conn, $time_check_sql));
 

    if ($check['total'] > 0) {
      $error = "This doctor already has an appointment at that time.";
    }
  }

  // =================[ UPDATE APPOINTMENT ]=================\\
  if (!$error) {
    $update_sql = "
      UPDATE appointments SET
        dentist_id = '$doctor',
        scheduled_datetime = '$datetime',
        status = '$status',
        dental_service_type = '$service'
      WHERE appointment_id = '$appointment_id'
    ";

    if (mysqli_query($conn, $update_sql)) {
      $success = "Appointment updated successfully.";
      // Reload form for instantaneous changes
      $appointment = mysqli_fetch_assoc(mysqli_query($conn, $appointment_sql));
    } else {
      $error = "Database error: " . mysqli_error($conn);
    }
  }
}

// =================[ DROPDOWNS ]=================\\

$dentists = mysqli_query($conn, "
  SELECT staff_id, first_name, last_name
  FROM staff_info
  WHERE staff_role = 'Dentist'
    AND is_active = 1
");

//==========[ We get dentists that are active ]==========\\

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Curaline – Edit Appointment</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex min-h-screen bg-[#F3F6FA] text-gray-800">

  <!------------- Sidebar ------------->
  <aside class="w-64 bg-[#2F5395] text-white flex flex-col">
    <div class="p-6 border-b border-[#3EDCDE]">
      <h1 class="text-3xl font-extrabold tracking-wide">Curaline</h1>
    </div>

    <nav class="flex-1 p-4 space-y-2">
      <a 
        href="dashboard.php" 
        class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition"
        >Dashboard</a>
      <a 

        href="patients.php" 
        class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition"
        >Patients</a>

      <a 
        href="staff.php" 
        class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition"
        >Staff</a>

      <a 
        href="appointments.php"  
        class="block py-2 px-4 rounded-lg bg-[#3EDCDE] text-[#F3F6FA] text-xl"
        >Appointments</a>
        
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

  <!------------- Main Content ------------->
  <div class="flex-1 flex flex-col">

    <!------------- Top Bar ------------->
    <header class="bg-white shadow p-4 flex justify-between items-center border-b border-[#E0E3E7]">
      <h1 class="text-2xl font-semibold text-[#2F5395]">Edit Appointment</h1>
      <span class="text-[#9FA2B2] font-medium">Admin</span>
    </header>

    <!------------- Page Content ------------->
    <main class="flex-1 p-6 flex justify-center">

      <div class="w-full max-w-3xl">

      <!-- Here we have error and success messages that use the $error and $success variables in each use case for notifications -->

        <?php if (!empty($error)) { ?>
          <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
            <?php echo $error; ?>
          </div>
        <?php } ?>

        <?php if (!empty($success)) { ?>
          <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
            <?php echo $success; ?>
          </div>
        <?php } ?>

        <div class="bg-white rounded-xl shadow p-6">

          <form method="POST" class="space-y-4">

            <!-- Doctor -->
            <div>
              <label class="block text-sm font-medium mb-1 text-[#2F5395]">Doctor</label>
              <select name="doctor" class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:ring-2 focus:ring-[#3EDCDE]">
                <?php while ($d = mysqli_fetch_assoc($dentists)) { ?>
                  <option value="<?= $d['staff_id']; ?>" <?= $appointment['dentist_id'] == $d['staff_id'] ? 'selected' : ''; ?>>
                    <?= $d['first_name'] . " " . $d['last_name']; ?>
                  </option>
                <?php } ?>
              </select>
            </div>

            <!-- Date -->
            <div>
              <label class="block text-sm font-medium mb-1 text-[#2F5395]">Date</label>
              <input type="date" name="date"
                value="<?= date('Y-m-d', strtotime($appointment['scheduled_datetime'])); ?>"
                class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:ring-2 focus:ring-[#3EDCDE]" />
            </div>

            <!-- Time -->
            <div>
              <label class="block text-sm font-medium mb-1 text-[#2F5395]">Time</label>
              <input type="time" name="time"
                value="<?= date('H:i', strtotime($appointment['scheduled_datetime'])); ?>"
                class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:ring-2 focus:ring-[#3EDCDE]" />
            </div>

            <!-- Status -->
            <div>
              <label class="block text-sm font-medium mb-1 text-[#2F5395]">Status</label>
              <select name="status" class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:ring-2 focus:ring-[#3EDCDE]">
                <?php
                  $statuses = ['Scheduled','Checked-In','In-Service','Completed','Cancelled','Missed'];
                  foreach ($statuses as $s) {
                    echo "<option " . ($appointment['status'] === $s ? 'selected' : '') . ">$s</option>";
                  }
                ?>
              </select>
            </div>

            <!-- Service -->
            <div>
              <label class="block text-sm font-medium mb-1 text-[#2F5395]">Dental Service</label>
              <input type="text" name="service"
                value="<?= $appointment['dental_service_type']; ?>"
                class="w-full border border-[#8FBFE0] rounded-lg p-2 focus:ring-2 focus:ring-[#3EDCDE]" />
            </div>

            <!-- Actions -->
            <div class="flex gap-4 pt-4">

            <!-- Save -->
            <button
                type="submit"
                class="bg-[#2F5395] text-white px-6 py-2 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition font-medium">
                Save Changes
            </button>

            <!-- Cancel -->
            <a
                href="appointments.php"
                class="px-6 py-2 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-100 transition">
                Back
            </a>
            
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
