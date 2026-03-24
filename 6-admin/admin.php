<?php

/*
- System Name: Curaline Clinic Appointment and Patient Management System (Curaline)
- Developers: Khalia Phillips, Havon James, and Tarik Wilson
- Version: V3
- Version Date: Mar 14, 2026
- Purpose of File: Administration page for creating and managing staff/patients
*/

require __DIR__ . '/../3-sessions/auth_admin.php';
include __DIR__ . '/../2-backend/db.php';

//===========================[ VALIDATION HELPERS ]===========================\\

function clean_post($conn, $key) {
  return mysqli_real_escape_string($conn, trim($_POST[$key] ?? ""));
}

function is_valid_email($email) {
  return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function is_valid_phone($phone) {
  $clean = preg_replace("/[^0-9]/", "", $phone);
  return (bool) preg_match("/^\d{7,15}$/", $clean);
}

function normalize_phone($phone) {
  return preg_replace("/[^0-9]/", "", $phone);
}

function is_valid_date($date) {
  $d = DateTime::createFromFormat("Y-m-d", $date);
  return $d && $d->format("Y-m-d") === $date;
}

function format_phone($phone) {
  $digits = preg_replace('/[^0-9]/', '', $phone);

  if (strlen($digits) === 11) {
    return substr($digits, 0, 1) . '-' . substr($digits, 1, 3) . '-' . substr($digits, 4, 3) . '-' . substr($digits, 7);
  }

  if (strlen($digits) === 10) {
    return substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6);
  }

  return $phone;
}

//===========================[ STATUS MESSAGES ]===========================\\

$error = null;
$success = null;

//=====================[ HANDLE CREATE ]=====================\\

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if (!isset($_POST['type'])) {
    $error = "Invalid form submission.";
  }

  //=====================[ CREATE STAFF ]=====================\\

  else if ($_POST['type'] === 'staff') {

    if (
      empty($_POST['staff_fname']) ||
      empty($_POST['staff_lname']) ||
      empty($_POST['staff_role']) ||
      empty($_POST['staff_phone']) ||
      empty($_POST['staff_email'])
    ) {
      $error = "All staff fields are required.";
    } else {

      $staff_fname = clean_post($conn, 'staff_fname');
      $staff_lname = clean_post($conn, 'staff_lname');
      $staff_role  = clean_post($conn, 'staff_role');
      $staff_phone = clean_post($conn, 'staff_phone');
      $staff_email = clean_post($conn, 'staff_email');

      $allowed_roles = ['Dentist','Nurse','Receptionist','Admin'];

      if (!in_array($staff_role, $allowed_roles, true)) {
        $error = 'Invalid staff role.';
      }

      if (!$error && !is_valid_phone($staff_phone)) {
        $error = 'Invalid phone number.';
      }

      if (!$error && !is_valid_email($staff_email)) {
        $error = 'Invalid email address.';
      }

      if (!$error && (strlen($staff_fname) > 50 || strlen($staff_lname) > 50)) {
        $error = 'Name fields must be 50 characters or less.';
      }

      if (!$error) {
        $dup = mysqli_fetch_assoc(
          mysqli_query($conn, "SELECT COUNT(*) AS c FROM staff_info WHERE email='$staff_email'")
        )['c'];

        if ((int)$dup > 0) {
          $error = 'A staff member with this email already exists.';
        }
      }

      if (!$error) {
        $staff_phone = normalize_phone($staff_phone);

        $prefix = $staff_role === 'Dentist' ? 'DEN' : ($staff_role === 'Nurse' ? 'NUR' : 'STA');

        $id_sql = "
          SELECT MAX(CAST(SUBSTRING(staff_id, 4) AS UNSIGNED)) AS max_num
          FROM staff_info
          WHERE staff_id LIKE '$prefix%'
        ";

        $id_result = mysqli_query($conn, $id_sql);
        $id_row = mysqli_fetch_assoc($id_result);
        $next_num = ((int)($id_row['max_num'] ?? 0)) + 1;

        $staff_id = $prefix . str_pad($next_num, 4, '0', STR_PAD_LEFT);

        $staff_sql = "
          INSERT INTO staff_info
          (
            staff_id,
            first_name,
            last_name,
            staff_role,
            phone_number,
            email,
            is_active
          )
          VALUES
          (
            '$staff_id',
            '$staff_fname',
            '$staff_lname',
            '$staff_role',
            '$staff_phone',
            '$staff_email',
            1
          )
        ";

        if (mysqli_query($conn, $staff_sql)) {
          $success = "Staff member added successfully.";
        } else {
          $error = "Database error: " . mysqli_error($conn);
        }
      }
    }
  }

  //=====================[ CREATE PATIENT ]=====================\\

  else if ($_POST['type'] === 'patient') {

    if (
      empty($_POST['first_name']) ||
      empty($_POST['last_name']) ||
      empty($_POST['phone_number']) ||
      empty($_POST['email']) ||
      empty($_POST['date_of_birth']) ||
      empty($_POST['sex']) ||
      empty($_POST['parish_of_residence']) ||
      empty($_POST['emergency_contact_name']) ||
      empty($_POST['emergency_contact_phone']) ||
      empty($_POST['emergency_contact_relationship']) ||
      !isset($_POST['has_allergies'])
    ) {
      $error = "All patient fields are required.";
    }

    if (!$error) {

      $count = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT COUNT(*) AS c FROM patient_info")
      )['c'] + 1;

      $patient_id = 'PAT' . str_pad($count, 4, '0', STR_PAD_LEFT);

      $first_name = clean_post($conn, 'first_name');
      $last_name = clean_post($conn, 'last_name');
      $dob = clean_post($conn, 'date_of_birth');
      $sex = clean_post($conn, 'sex');
      $phone = clean_post($conn, 'phone_number');
      $email = clean_post($conn, 'email');
      $parish = clean_post($conn, 'parish_of_residence');

      $em_name = clean_post($conn, 'emergency_contact_name');
      $em_phone = clean_post($conn, 'emergency_contact_phone');
      $em_relation = clean_post($conn, 'emergency_contact_relationship');

      $has_allergies = clean_post($conn, 'has_allergies');
      $allergy_details = isset($_POST['allergy_details']) ? clean_post($conn, 'allergy_details') : null;

      $allowed_sex = ['Male','Female','Other'];
      $allowed_parish = [
        'Kingston','St. Andrew','St. Catherine','Clarendon','Manchester',
        'St. Elizabeth','Westmorland','Hanover','St. James','Trelawny',
        'St. Ann','St. Mary','Portland','St. Thomas'
      ];

      if (!in_array($sex, $allowed_sex, true)) {
        $error = 'Invalid sex selected.';
      }

      if (!$error && !in_array($parish, $allowed_parish, true)) {
        $error = 'Invalid parish selected.';
      }

      if (!$error && !is_valid_date($dob)) {
        $error = 'Invalid date of birth.';
      }

      if (!$error) {
        $today = date('Y-m-d');
        if ($dob > $today) {
          $error = 'Date of birth cannot be in the future.';
        }
      }

      if (!$error && !is_valid_phone($phone)) {
        $error = 'Invalid phone number.';
      }

      if (!$error && !is_valid_email($email)) {
        $error = 'Invalid email address.';
      }

      if (!$error && !is_valid_phone($em_phone)) {
        $error = 'Invalid emergency contact phone.';
      }

      if (!$error && (strlen($first_name) > 50 || strlen($last_name) > 50 || strlen($em_name) > 80)) {
        $error = 'Name fields are too long.';
      }

      if (!$error && !in_array($has_allergies, ['0','1'], true)) {
        $error = 'Invalid allergy selection.';
      }

      if (!$error) {
        $dup_p = mysqli_fetch_assoc(
          mysqli_query($conn, "SELECT COUNT(*) AS c FROM patient_info WHERE email='$email'")
        )['c'];

        if ((int)$dup_p > 0) {
          $error = 'A patient with this email already exists.';
        }
      }

      if (!$error && $has_allergies == '1' && $allergy_details !== null && strlen($allergy_details) > 255) {
        $error = 'Allergy details must be 255 characters or less.';
      }

      if ($has_allergies == '0') {
        $allergy_details = NULL;
      }

      if (!$error && $has_allergies == '1' && empty($allergy_details)) {
        $error = "Please specify allergy details.";
      }

      if (!$error) {
        $phone = normalize_phone($phone);
        $em_phone = normalize_phone($em_phone);

        $sql = "
          INSERT INTO patient_info
          (
            patient_id,
            first_name,
            last_name,
            date_of_birth,
            sex,
            phone_number,
            email,
            parish_of_residence,
            emergency_contact_name,
            emergency_contact_phone,
            emergency_contact_relationship,
            has_allergies,
            allergy_details,
            is_active
          )
          VALUES
          (
            '$patient_id',
            '$first_name',
            '$last_name',
            '$dob',
            '$sex',
            '$phone',
            '$email',
            '$parish',
            '$em_name',
            '$em_phone',
            '$em_relation',
            '$has_allergies',
            ".($allergy_details === NULL ? "NULL" : "'$allergy_details'").",
            1
          )
        ";

        if (mysqli_query($conn, $sql)) {
          $success = "Patient added successfully.";
        } else {
          $error = "Database error: " . mysqli_error($conn);
        }
      }
    }
  }
}

//=====================[ LIST TABLES ]=====================\\

$staff_list_sql = "
  SELECT
    staff_id,
    first_name,
    last_name,
    staff_role,
    phone_number,
    email,
    is_active
  FROM staff_info
  ORDER BY staff_id ASC
";

$patient_list_sql = "
  SELECT
    patient_id,
    first_name,
    last_name,
    phone_number,
    email,
    sex,
    is_active
  FROM patient_info
  ORDER BY patient_id ASC
";

$staff_list_result = mysqli_query($conn, $staff_list_sql);
$patient_list_result = mysqli_query($conn, $patient_list_sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Curaline – Admin Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../1-assets/ui.css">
  
</head>

<body class="flex min-h-screen bg-gradient-to-br from-[#EEF3FA] to-[#C9D8F0] text-gray-800">

  <!------------ SIDEBAR ------------>
  <aside class="w-64 bg-gradient-to-b from-[#2F5395] to-[#26457C] text-white flex flex-col shadow-xl">

    <!-- Logo -->
    <div class="px-6 py-6 border-b border-white/10 flex items-center justify-center">
      <img src="../1-assets/curalineWhiteLogo.png" alt="Curaline" class="h-12 w-auto">
    </div>

    <!-- Navigation -->
    <nav class="flex-1 p-4 space-y-2">

      <a href="../5-staff/dashboard.php"
        class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-all duration-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 3v18h18M9 17V9m4 8V5m4 12v-4" />
        </svg>
        <span>Dashboard</span>
      </a>

      <a href="../5-staff/patients.php"
        class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-all duration-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M16 14a4 4 0 10-8 0m8 0a4 4 0 10-8 0m8 0v1a2 2 0 002 2h1m-11-3v1a2 2 0 01-2 2H4" />
        </svg>
        <span>Patients</span>
      </a>

      <a href="../5-staff/staff.php"
        class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-all duration-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M5.121 17.804A7.969 7.969 0 0112 15c2.136 0 4.07.84 5.879 2.204M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <span>Staff</span>
      </a>

      <a href="../5-staff/appointments.php"
        class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-all duration-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M8 7V3m8 4V3m-9 8h10m-11 8h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
        <span>Appointments</span>
      </a>

      <?php if ($_SESSION['staff_role'] === 'Admin'): ?>
        <a href="../6-admin/admin.php"
          class="flex items-center gap-3 px-4 py-3 rounded-xl bg-[#3EDCDE] text-[#2F5395] font-medium shadow-md transition-all duration-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M11.983 5.176a2 2 0 012.034 0l1.607.93a2 2 0 001.518.18l1.78-.48a2 2 0 012.452 2.452l-.48 1.78a2 2 0 00.18 1.518l.93 1.607a2 2 0 010 2.034l-.93 1.607a2 2 0 00-.18 1.518l.48 1.78a2 2 0 01-2.452 2.452l-1.78-.48a2 2 0 00-1.518.18l-1.607.93a2 2 0 01-2.034 0l-1.607-.93a2 2 0 00-1.518-.18l-1.78.48a2 2 0 01-2.452-2.452l.48-1.78a2 2 0 00-.18-1.518l-.93-1.607a2 2 0 010-2.034l.93-1.607a2 2 0 00.18-1.518l-.48-1.78a2 2 0 012.452-2.452l1.78.48a2 2 0 001.518-.18l1.607-.93z" />
          </svg>
          <span>Admin</span>
        </a>
      <?php endif; ?>

    </nav>

    <!-- Bottom block -->
    <div class="p-4 border-t border-white/10 space-y-3 mt-auto">
      <div class="flex items-center gap-3 px-2">
        <div class="w-10 h-10 rounded-full bg-[#3EDCDE] flex items-center justify-center font-bold text-white shrink-0">
          <?= strtoupper(substr($_SESSION['staff_role'], 0, 1)) ?>
        </div>
        <div class="text-sm min-w-0">
          <p class="font-medium leading-tight"><?= $_SESSION['staff_role'] ?></p>
          <p class="text-xs text-white/60 leading-tight">ID: <?= $_SESSION['user_id'] ?></p>
        </div>
      </div>

      <a href="../3-sessions/logout.php"
        class="block w-full text-center bg-white/20 hover:bg-white/30 transition-all duration-200 py-2 rounded-xl text-sm font-semibold">
        Logout
      </a>
    </div>

  </aside>

    <!------------ Main content ------------>

  <div class="flex-1 p-6 space-y-6 max-w-6xl">

    <header class="app-card p-6 flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-[#2F5395]">Admin</h1>
        <p class="text-sm text-gray-500 mt-1">Manage staff and patient records</p>
      </div>

      <div class="flex items-center gap-3">
        <span class="text-sm text-[#9FA2B2]">
          ID: <?= $_SESSION['user_id'] ?>
        </span>
        <div class="w-10 h-10 flex items-center justify-center rounded-full bg-[#3EDCDE] text-white font-semibold">
          <?= strtoupper(substr($_SESSION['staff_role'], 0, 1)) ?>
        </div>
      </div>
    </header>


    <main class="flex-1 p-6 space-y-6">

      <!------------ CREATE FORM CARD ------------>

      <div class="app-card bg-white shadow rounded-xl p-6 w-full max-w-xl">
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

        <form method="POST" class="space-y-4">

          <div>
            <label class="text-sm font-medium text-[#2F5395]">
              What are you adding?
            </label>
            <select name="type" id="typeSelect"
              onchange="toggleForms()"
              class="w-full border rounded-lg p-2">
              <option value="">Select</option>
              <option value="staff" <?= ($_POST['type'] ?? '') === 'staff' ? 'selected' : '' ?>>Staff</option>
              <option value="patient" <?= ($_POST['type'] ?? '') === 'patient' ? 'selected' : '' ?>>Patient</option>
            </select>
          </div>

          <!------------ STAFF FORM ------------>
          <div id="staffForm" class="hidden space-y-3">
            <div>
              <label class="text-sm font-medium text-[#2F5395]">First Name</label>
              <input name="staff_fname" placeholder="First Name" class="w-full border rounded-lg p-2">
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Last Name</label>
              <input name="staff_lname" placeholder="Last Name" class="w-full border rounded-lg p-2">
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Staff Role</label>
              <select name="staff_role" class="w-full border rounded-lg p-2">
                <option value="">Select Role</option>
                <option value="Dentist">Dentist</option>
                <option value="Nurse">Nurse</option>
                <option value="Receptionist">Receptionist</option>
                <option value="Admin">Admin</option>
              </select>
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Phone Number</label>
              <input name="staff_phone" placeholder="876-453-2354" class="w-full border rounded-lg p-2">
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Email Address</label>
              <input name="staff_email" placeholder="example@gmail.com" class="w-full border rounded-lg p-2">
            </div>
          </div>

          <!------------ PATIENT FORM ------------>
          <div id="patientForm" class="hidden space-y-3">

            <div>
              <label class="text-sm font-medium text-[#2F5395]">First Name</label>
              <input type="text" name="first_name" class="w-full border border-[#8FBFE0] rounded-lg p-2" placeholder="Enter first name" />
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Last Name</label>
              <input type="text" name="last_name" class="w-full border border-[#8FBFE0] rounded-lg p-2" placeholder="Enter last name" />
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Sex</label>
              <select name="sex" class="w-full border border-[#8FBFE0] rounded-lg p-2">
                <option value="">Select</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Date of Birth</label>
              <input type="date" name="date_of_birth" class="w-full border border-[#8FBFE0] rounded-lg p-2" />
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Phone Number</label>
              <input type="text" name="phone_number" class="w-full border border-[#8FBFE0] rounded-lg p-2" placeholder="e.g 876-555-1234" />
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Email</label>
              <input type="email" name="email" class="w-full border border-[#8FBFE0] rounded-lg p-2" placeholder="example@email.com">
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Parish</label>
              <select name="parish_of_residence" class="w-full border border-[#8FBFE0] rounded-lg p-2">
                <option value="">Select Parish</option>
                <option>Kingston</option>
                <option>St. Andrew</option>
                <option>St. Catherine</option>
                <option>Clarendon</option>
                <option>Manchester</option>
                <option>St. Elizabeth</option>
                <option>Westmorland</option>
                <option>Hanover</option>
                <option>St. James</option>
                <option>Trelawny</option>
                <option>St. Ann</option>
                <option>St. Mary</option>
                <option>Portland</option>
                <option>St. Thomas</option>
              </select>
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Allergies</label>
              <select name="has_allergies" class="w-full border border-[#8FBFE0] rounded-lg p-2">
                <option value="">Select</option>
                <option value="0">No</option>
                <option value="1">Yes</option>
              </select>
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Allergy Details (if any)</label>
              <input type="text" name="allergy_details" placeholder="e.g Penicillin, Latex" class="w-full border border-[#8FBFE0] rounded-lg p-2">
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Emergency Contact Name</label>
              <input type="text" name="emergency_contact_name" placeholder="Full name" class="w-full border border-[#8FBFE0] rounded-lg p-2" />
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Emergency Contact Phone</label>
              <input type="text" name="emergency_contact_phone" placeholder="e.g 1876-555-1234" class="w-full border border-[#8FBFE0] rounded-lg p-2" />
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Emergency Contact Relation</label>
              <select name="emergency_contact_relationship" class="w-full border border-[#8FBFE0] rounded-lg p-2">
                <option value="">Select</option>
                <option value="Husband">Husband</option>
                <option value="Wife">Wife</option>
                <option value="Child">Child</option>
                <option value="Mother">Mother</option>
                <option value="Father">Father</option>
                <option value="Sibling">Sibling</option>
                <option value="Cousin">Cousin</option>
                <option value="Friend">Friend</option>
                <option value="Guardian">Guardian</option>
              </select>
            </div>
          </div>

          <button
            id="submitButton"
            class="bg-[#2F5395] text-white w-full py-2 rounded-lg hover:bg-[#3EDCDE] transition">
            Create
          </button>
        </form>
      </div>

      <!------------ STAFF RECORDS TABLE ------------>

      <div class="app-card bg-white shadow rounded-xl p-6 w-full">
        <h2 class="text-xl font-semibold text-[#2F5395] mb-4">Staff Records</h2>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-[#8FBFE0]">
                <th class="py-2">Staff ID</th>
                <th class="py-2">Full Name</th>
                <th class="py-2">Role</th>
                <th class="py-2">Phone</th>
                <th class="py-2">Email</th>
                <th class="py-2">Status</th>
                <th class="py-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($staff_list_result && mysqli_num_rows($staff_list_result) > 0): ?>
                <?php while ($staff_row = mysqli_fetch_assoc($staff_list_result)): ?>
                  <tr class="border-b">
                    <td class="py-2"><?= $staff_row['staff_id'] ?></td>
                    <td class="py-2"><?= $staff_row['first_name'] . " " . $staff_row['last_name'] ?></td>
                    <td class="py-2"><?= $staff_row['staff_role'] ?></td>
                    <td class="py-2"><?= format_phone($staff_row['phone_number']) ?></td>
                    <td class="py-2"><?= $staff_row['email'] ?></td>
                    <td class="py-2"><?= $staff_row['is_active'] ? 'Active' : 'Inactive' ?></td>
                    <td class="py-2">
                      <a href="edit_staff.php?staff_id=<?= $staff_row['staff_id'] ?>"
                        class="text-[#2F5395] hover:underline font-medium">
                        Edit
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="py-3 text-gray-500">No staff records found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!------------ PATIENT RECORDS TABLE ------------>

      <div class="app-card bg-white shadow rounded-xl p-6 w-full">
        <h2 class="text-xl font-semibold text-[#2F5395] mb-4">Patient Records</h2>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-[#8FBFE0]">
                <th class="py-2">Patient ID</th>
                <th class="py-2">Full Name</th>
                <th class="py-2">Sex</th>
                <th class="py-2">Phone</th>
                <th class="py-2">Email</th>
                <th class="py-2">Status</th>
                <th class="py-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($patient_list_result && mysqli_num_rows($patient_list_result) > 0): ?>
                <?php while ($patient_row = mysqli_fetch_assoc($patient_list_result)): ?>
                  <tr class="border-b">
                    <td class="py-2"><?= $patient_row['patient_id'] ?></td>
                    <td class="py-2"><?= $patient_row['first_name'] . " " . $patient_row['last_name'] ?></td>
                    <td class="py-2"><?= $patient_row['sex'] ?></td>
                    <td class="py-2"><?= format_phone($patient_row['phone_number']) ?></td>
                    <td class="py-2"><?= $patient_row['email'] ?></td>
                    <td class="py-2"><?= $patient_row['is_active'] ? 'Active' : 'Inactive' ?></td>
                    <td class="py-2">
                      <a href="edit_patient.php?patient_id=<?= $patient_row['patient_id'] ?>"
                        class="text-[#2F5395] hover:underline font-medium">
                        Edit
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="py-3 text-gray-500">No patient records found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </main>
  </div>

  <script>
    function toggleForms() {
      const type = document.getElementById('typeSelect').value;
      const staff = document.getElementById('staffForm');
      const patient = document.getElementById('patientForm');

      staff.querySelectorAll('input, select').forEach(el => el.disabled = true);
      patient.querySelectorAll('input, select').forEach(el => el.disabled = true);

      staff.classList.add('hidden');
      patient.classList.add('hidden');

      if (type === 'staff') {
        staff.classList.remove('hidden');
        staff.querySelectorAll('input, select').forEach(el => el.disabled = false);
      }

      if (type === 'patient') {
        patient.classList.remove('hidden');
        patient.querySelectorAll('input, select').forEach(el => el.disabled = false);
      }
    }

    window.onload = toggleForms;
  </script>

</body>
</html>