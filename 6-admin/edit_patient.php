<?php

/*
- System Name: Curaline Clinic Appointment and Patient Management System (Curaline)
- Developers: Khalia Phillips, Havon James, and Tarik Wilson
- Version: V3
- Version Date: Mar 14, 2026
- Purpose of File: Administration page for editing patients and patient login credentials
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

if (!isset($_GET['patient_id'])) {
  die("Invalid patient.");
}

$patient_id = mysqli_real_escape_string($conn, $_GET['patient_id']);
$error = null;
$success = null;

//===========================[ LOAD PATIENT INFO ]===========================\\

$patient_sql = "
  SELECT *
  FROM patient_info
  WHERE patient_id = '$patient_id'
";

$patient_result = mysqli_query($conn, $patient_sql);
$patient = mysqli_fetch_assoc($patient_result);

if (!$patient) {
  die("Patient not found.");
}

//===========================[ LOAD PATIENT AUTH ]===========================\\

$auth_sql = "
  SELECT *
  FROM patient_auth
  WHERE patient_id = '$patient_id'
";

$auth_result = mysqli_query($conn, $auth_sql);
$patient_auth = mysqli_fetch_assoc($auth_result);

//===========================[ HANDLE RESET MFA ]===========================\\

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_mfa'])) {
    $stmt = $conn->prepare("UPDATE patient_info SET mfa_secret = NULL, mfa_enabled = 0 WHERE patient_id = ?");
    $stmt->bind_param("s", $patient_id);
    $stmt->execute();

    header("Location: edit_patient.php?patient_id=" . urlencode($patient_id) . "&mfa_reset=1");
    exit;
}

//===========================[ HANDLE DEACTIVATE ]===========================\\

if (isset($_POST['deactivate_patient'])) {

  $deactivate_info_sql = "
    UPDATE patient_info
    SET is_active = 0
    WHERE patient_id = '$patient_id'
  ";

  mysqli_query($conn, $deactivate_info_sql);

  $deactivate_auth_sql = "
    UPDATE patient_auth
    SET is_active = 0
    WHERE patient_id = '$patient_id'
  ";

  mysqli_query($conn, $deactivate_auth_sql);

  header("Location: admin.php");
  exit;
}

//===========================[ HANDLE SAVE ]===========================\\

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_patient'])) {

  $first_name = clean_post($conn, 'first_name');
  $last_name = clean_post($conn, 'last_name');
  $date_of_birth = clean_post($conn, 'date_of_birth');
  $sex = clean_post($conn, 'sex');
  $phone_number = clean_post($conn, 'phone_number');
  $email = clean_post($conn, 'email');
  $parish = clean_post($conn, 'parish_of_residence');
  $em_name = clean_post($conn, 'emergency_contact_name');
  $em_phone = clean_post($conn, 'emergency_contact_phone');
  $em_relation = clean_post($conn, 'emergency_contact_relationship');
  $has_allergies = clean_post($conn, 'has_allergies');
  $allergy_details = clean_post($conn, 'allergy_details');
  $is_active = clean_post($conn, 'is_active');

  $username = clean_post($conn, 'username');
  $new_password = trim($_POST['new_password'] ?? '');
  $confirm_password = trim($_POST['confirm_password'] ?? '');

  if (
    !$first_name || !$last_name || !$date_of_birth || !$sex || !$phone_number || !$email ||
    !$parish || !$em_name || !$em_phone || !$em_relation
  ) {
    $error = "All required profile fields must be filled.";
  } else {

    $allowed_sex = ['Male', 'Female', 'Other'];
    $allowed_parish = [
      'Kingston','St. Andrew','St. Catherine','Clarendon','Manchester',
      'St. Elizabeth','Westmorland','Hanover','St. James','Trelawny',
      'St. Ann','St. Mary','Portland','St. Thomas'
    ];

    if (!in_array($sex, $allowed_sex, true)) {
      $error = "Invalid sex selected.";
    }

    if (!$error && !in_array($parish, $allowed_parish, true)) {
      $error = "Invalid parish selected.";
    }

    if (!$error && !is_valid_phone($phone_number)) {
      $error = "Invalid phone number.";
    }

    if (!$error && !is_valid_phone($em_phone)) {
      $error = "Invalid emergency contact phone.";
    }

    if (!$error && !is_valid_email($email)) {
      $error = "Invalid email address.";
    }

    if (!$error && !in_array($has_allergies, ['0','1'], true)) {
      $error = "Invalid allergy selection.";
    }

    if (!$error && !in_array($is_active, ['0','1'], true)) {
      $error = "Invalid active status.";
    }

    if (!$error) {
      $dup_email_sql = "
        SELECT COUNT(*) AS c
        FROM patient_info
        WHERE email = '$email'
          AND patient_id != '$patient_id'
      ";
      $dup_email_result = mysqli_query($conn, $dup_email_sql);
      $dup_email_row = mysqli_fetch_assoc($dup_email_result);

      if ((int)$dup_email_row['c'] > 0) {
        $error = "Another patient already uses this email.";
      }
    }

    // Username validation only if one is entered or an auth row already exists
    if (!$error && ($username !== '' || $patient_auth)) {

      if ($username === '') {
        $error = "Username is required for login credentials.";
      }

      if (!$error) {
        $dup_username_sql = "
          SELECT COUNT(*) AS c
          FROM patient_auth
          WHERE username = '$username'
            AND patient_id != '$patient_id'
        ";
        $dup_username_result = mysqli_query($conn, $dup_username_sql);
        $dup_username_row = mysqli_fetch_assoc($dup_username_result);

        if ((int)$dup_username_row['c'] > 0) {
          $error = "That username is already in use.";
        }
      }
    }

    if (!$error && ($new_password !== '' || $confirm_password !== '')) {
      if ($new_password !== $confirm_password) {
        $error = "New password and confirm password do not match.";
      } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters.";
      }
    }

    if (!$error && !$patient_auth && $username !== '' && $new_password === '') {
      $error = "A password is required when creating login credentials for this patient.";
    }

    if (!$error) {

      $phone_number = normalize_phone($phone_number);
      $em_phone = normalize_phone($em_phone);

      if ($has_allergies === '0') {
        $allergy_details = '';
      }

      //==========[ UPDATE patient_info ]==========\\

      $update_info_sql = "
        UPDATE patient_info
        SET
          first_name = '$first_name',
          last_name = '$last_name',
          date_of_birth = '$date_of_birth',
          sex = '$sex',
          phone_number = '$phone_number',
          email = '$email',
          parish_of_residence = '$parish',
          emergency_contact_name = '$em_name',
          emergency_contact_phone = '$em_phone',
          emergency_contact_relationship = '$em_relation',
          has_allergies = '$has_allergies',
          allergy_details = " . ($allergy_details === '' ? "NULL" : "'$allergy_details'") . ",
          is_active = '$is_active'
        WHERE patient_id = '$patient_id'
      ";

      if (!mysqli_query($conn, $update_info_sql)) {
        $error = "Database error: " . mysqli_error($conn);
      }
    }

    if (!$error) {

      //==========[ UPDATE OR INSERT patient_auth ]==========\\

      if ($patient_auth) {

        $update_auth_sql = "
          UPDATE patient_auth
          SET
            username = '$username',
            is_active = '$is_active'
          WHERE patient_id = '$patient_id'
        ";

        if (!mysqli_query($conn, $update_auth_sql)) {
          $error = "Failed to update patient login credentials: " . mysqli_error($conn);
        }

        if (!$error && $new_password !== '') {
          $password_hash = hash('sha256', $new_password);

          $update_password_sql = "
            UPDATE patient_auth
            SET password_hash = '$password_hash'
            WHERE patient_id = '$patient_id'
          ";

          if (!mysqli_query($conn, $update_password_sql)) {
            $error = "Failed to update password: " . mysqli_error($conn);
          }
        }

      } else {

        // Only create auth row if admin entered a username
        if ($username !== '') {

          $password_hash = hash('sha256', $new_password);

          $insert_auth_sql = "
            INSERT INTO patient_auth
            (
              patient_id,
              username,
              password_hash,
              is_active
            )
            VALUES
            (
              '$patient_id',
              '$username',
              '$password_hash',
              '$is_active'
            )
          ";

          if (!mysqli_query($conn, $insert_auth_sql)) {
            $error = "Failed to create patient login credentials: " . mysqli_error($conn);
          }
        }
      }
    }

    if (!$error) {
      $success = "Patient updated successfully.";

      $patient_result = mysqli_query($conn, $patient_sql);
      $patient = mysqli_fetch_assoc($patient_result);

      $auth_result = mysqli_query($conn, $auth_sql);
      $patient_auth = mysqli_fetch_assoc($auth_result);
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Curaline – Edit Patient</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../1-assets/ui.css">
</head>
<body class="flex min-h-screen bg-gradient-to-br from-[#EEF3FA] to-[#C9D8F0] text-gray-800">

  <!------------ SIDEBAR ------------>
  <aside class="w-64 bg-gradient-to-b from-[#2F5395] to-[#26457C] text-white flex flex-col shadow-xl sticky top-0 h-screen">

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

    <main class="flex-1 p-6 flex justify-center">
      <div class="w-full max-w-4xl">

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

        <div class="bg-white shadow rounded-xl p-6">
          <form method="POST" class="space-y-4">

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Patient ID</label>
              <input
                value="<?= htmlspecialchars($patient['patient_id']) ?>"
                class="w-full border rounded-lg p-2 bg-gray-100"
                disabled
              >
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">First Name</label>
              <input name="first_name" value="<?= htmlspecialchars($patient['first_name']) ?>" class="w-full border rounded-lg p-2">
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Last Name</label>
              <input name="last_name" value="<?= htmlspecialchars($patient['last_name']) ?>" class="w-full border rounded-lg p-2">
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Date of Birth</label>
              <input type="date" name="date_of_birth" value="<?= htmlspecialchars($patient['date_of_birth']) ?>" class="w-full border rounded-lg p-2">
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Sex</label>
              <select name="sex" class="w-full border rounded-lg p-2">
                <?php
                  $sexes = ['Male','Female','Other'];
                  foreach ($sexes as $s) {
                    $selected = $patient['sex'] === $s ? 'selected' : '';
                    echo "<option value=\"$s\" $selected>$s</option>";
                  }
                ?>
              </select>
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Phone Number</label>
              <input
                name="phone_number"
                value="<?= htmlspecialchars(format_phone($patient['phone_number'])) ?>"
                class="w-full border rounded-lg p-2"
              >
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Email</label>
              <input name="email" value="<?= htmlspecialchars($patient['email']) ?>" class="w-full border rounded-lg p-2">
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Parish</label>
              <select name="parish_of_residence" class="w-full border rounded-lg p-2">
                <?php
                  $parishes = [
                    'Kingston','St. Andrew','St. Catherine','Clarendon','Manchester',
                    'St. Elizabeth','Westmorland','Hanover','St. James','Trelawny',
                    'St. Ann','St. Mary','Portland','St. Thomas'
                  ];
                  foreach ($parishes as $parish) {
                    $selected = $patient['parish_of_residence'] === $parish ? 'selected' : '';
                    echo "<option value=\"$parish\" $selected>$parish</option>";
                  }
                ?>
              </select>
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Allergies</label>
              <select name="has_allergies" class="w-full border rounded-lg p-2">
                <option value="0" <?= $patient['has_allergies'] == 0 ? 'selected' : '' ?>>No</option>
                <option value="1" <?= $patient['has_allergies'] == 1 ? 'selected' : '' ?>>Yes</option>
              </select>
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Allergy Details</label>
              <input name="allergy_details" value="<?= htmlspecialchars($patient['allergy_details'] ?? '') ?>" class="w-full border rounded-lg p-2">
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Emergency Contact Name</label>
              <input name="emergency_contact_name" value="<?= htmlspecialchars($patient['emergency_contact_name']) ?>" class="w-full border rounded-lg p-2">
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Emergency Contact Phone</label>
              <input
                name="emergency_contact_phone"
                value="<?= htmlspecialchars(format_phone($patient['emergency_contact_phone'])) ?>"
                class="w-full border rounded-lg p-2"
              >
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Emergency Contact Relationship</label>
              <input
                name="emergency_contact_relationship"
                value="<?= htmlspecialchars($patient['emergency_contact_relationship']) ?>"
                class="w-full border rounded-lg p-2"
              >
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Status</label>
              <select name="is_active" class="w-full border rounded-lg p-2">
                <option value="1" <?= $patient['is_active'] == 1 ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= $patient['is_active'] == 0 ? 'selected' : '' ?>>Inactive</option>
              </select>
            </div>

            <hr class="my-4">

            <h2 class="text-lg font-semibold text-[#2F5395]">Login Credentials</h2>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Username</label>
              <input
                name="username"
                value="<?= htmlspecialchars($patient_auth['username'] ?? '') ?>"
                class="w-full border rounded-lg p-2"
                placeholder="Enter username"
              >
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">New Password</label>
              <div class="relative">
                <input
                  type="password"
                  id="new_password"
                  name="new_password"
                  class="w-full border rounded-lg p-2 pr-10"
                  placeholder="Leave blank to keep current password"
                >
                <button
                  type="button"
                  onclick="togglePassword('new_password')"
                  class="absolute right-2 top-2 text-sm text-gray-500"
                >
                  show
                </button>
              </div>
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Confirm New Password</label>
              <div class="relative">
                <input
                  type="password"
                  id="confirm_password"
                  name="confirm_password"
                  class="w-full border rounded-lg p-2 pr-10"
                  placeholder="Re-enter new password"
                >
                <button
                  type="button"
                  onclick="togglePassword('confirm_password')"
                  class="absolute right-2 top-2 text-sm text-gray-500"
                >
                  show
                </button>
              </div>
            </div>

            <?php if (!$patient_auth): ?>
              <p class="text-sm text-orange-600">
                This patient does not currently have login credentials. Enter a username and password to create them.
              </p>
            <?php endif; ?>

            <div class="flex gap-3 pt-4">
              <button
                type="submit"
                name="save_patient"
                class="bg-[#2F5395] text-white px-6 py-2 rounded-lg hover:bg-[#3EDCDE] transition"
              >
                Save Changes
              </button>

              <button
                type="submit"
                name="deactivate_patient"
                class="bg-[#9FA2B2] text-white px-6 py-2 rounded-lg hover:opacity-90 transition"
              >
                Deactivate
              </button>

              <a
                href="admin.php"
                class="px-6 py-2 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-100 transition"
              >
                Back
              </a>

              <button
                type="submit"
                name="reset_mfa"
                onclick="return confirm('Reset MFA for this patient? They will need to set it up again on next login.');"
                class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-all duration-200">
                Reset MFA
              </button>

            </div>

          </form>
        </div>
      </div>
    </main>
  </div>

  <script>
    function togglePassword(fieldId) {
      const input = document.getElementById(fieldId);

      if (input.type === "password") {
        input.type = "text";
      } else {
        input.type = "password";
      }
    }
  </script>

</body>
</html>