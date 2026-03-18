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

if (!isset($_GET['staff_id'])) {
  die("Invalid staff member.");
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

$staff_id = mysqli_real_escape_string($conn, $_GET['staff_id']);
$error = null;
$success = null;

$staff_sql = "
  SELECT *
  FROM staff_info
  WHERE staff_id = '$staff_id'
";

$staff_result = mysqli_query($conn, $staff_sql);
$staff = mysqli_fetch_assoc($staff_result);

if (!$staff) {
  die("Staff member not found.");
}

if (isset($_POST['deactivate_staff'])) {
  $deactivate_sql = "
    UPDATE staff_info
    SET is_active = 0
    WHERE staff_id = '$staff_id'
  ";

  if (mysqli_query($conn, $deactivate_sql)) {
    header("Location: admin.php");
    exit;
  } else {
    $error = "Failed to deactivate staff member.";
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_staff'])) {
  $first_name = clean_post($conn, 'first_name');
  $last_name = clean_post($conn, 'last_name');
  $staff_role = clean_post($conn, 'staff_role');
  $phone_number = clean_post($conn, 'phone_number');
  $email = clean_post($conn, 'email');
  $is_active = clean_post($conn, 'is_active');

  if (!$first_name || !$last_name || !$staff_role || !$phone_number || !$email) {
    $error = "All fields are required.";
  } else {
    $allowed_roles = ['Dentist', 'Nurse', 'Receptionist', 'Admin'];

    if (!in_array($staff_role, $allowed_roles, true)) {
      $error = "Invalid staff role.";
    }

    if (!$error && !is_valid_phone($phone_number)) {
      $error = "Invalid phone number.";
    }

    if (!$error && !is_valid_email($email)) {
      $error = "Invalid email address.";
    }

    if (!$error && !in_array($is_active, ['0', '1'], true)) {
      $error = "Invalid active status.";
    }

    if (!$error) {
      $dup_sql = "
        SELECT COUNT(*) AS c
        FROM staff_info
        WHERE email = '$email'
          AND staff_id != '$staff_id'
      ";
      $dup_result = mysqli_query($conn, $dup_sql);
      $dup_row = mysqli_fetch_assoc($dup_result);

      if ((int)$dup_row['c'] > 0) {
        $error = "Another staff member already uses this email.";
      }
    }

    if (!$error) {
      $phone_number = normalize_phone($phone_number);

      $update_sql = "
        UPDATE staff_info
        SET
          first_name = '$first_name',
          last_name = '$last_name',
          staff_role = '$staff_role',
          phone_number = '$phone_number',
          email = '$email',
          is_active = '$is_active'
        WHERE staff_id = '$staff_id'
      ";

      if (mysqli_query($conn, $update_sql)) {
        $success = "Staff member updated successfully.";
        $staff_result = mysqli_query($conn, $staff_sql);
        $staff = mysqli_fetch_assoc($staff_result);
      } else {
        $error = "Database error: " . mysqli_error($conn);
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
  <title>Curaline – Edit Staff</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen bg-[#F3F6FA] text-gray-800">

  <aside class="w-64 bg-[#2F5395] text-white flex flex-col">
    <div class="p-6 border-b border-[#3EDCDE] flex justify-center">
      <img src="../1-assets/curalineWhiteLogo.png" alt="Curaline" class="h-12 w-auto">
    </div>

    <nav class="flex-1 p-4 space-y-2">
      <a href="../5-staff/dashboard.php" class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition">Dashboard</a>
      <a href="../5-staff/patients.php" class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition">Patients</a>
      <a href="../5-staff/staff.php" class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition">Staff</a>
      <a href="../5-staff/appointments.php" class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition">Appointments</a>
      <a href="../6-admin/admin.php" class="block py-2 px-4 rounded-lg bg-[#3EDCDE] text-[#F3F6FA] transition">Admin</a>
    </nav>

    <div class="p-4 mt-auto">
      <a href="../3-sessions/logout.php" class="w-full block text-center bg-[#9FA2B2] py-2 rounded-lg font-semibold">Logout</a>
    </div>
  </aside>

  <div class="flex-1 flex flex-col">
    <header class="bg-white shadow p-4 flex justify-between items-center border-b border-[#E0E3E7]">
      <h1 class="text-2xl font-semibold text-[#2F5395]">Edit Staff Member</h1>
      <span class="text-[#9FA2B2] font-medium">
        <?= $_SESSION['staff_role'] ?> • <?= $_SESSION['user_id'] ?>
      </span>
    </header>

    <main class="flex-1 p-6 flex justify-center">
      <div class="w-full max-w-3xl">

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
              <label class="text-sm font-medium text-[#2F5395]">Staff ID</label>
              <input
                value="<?= htmlspecialchars($staff['staff_id']) ?>"
                class="w-full border rounded-lg p-2 bg-gray-100"
                disabled
              >
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">First Name</label>
              <input
                name="first_name"
                value="<?= htmlspecialchars($staff['first_name']) ?>"
                class="w-full border rounded-lg p-2"
              >
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Last Name</label>
              <input
                name="last_name"
                value="<?= htmlspecialchars($staff['last_name']) ?>"
                class="w-full border rounded-lg p-2"
              >
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Staff Role</label>
              <select name="staff_role" class="w-full border rounded-lg p-2">
                <?php
                  $roles = ['Dentist','Nurse','Receptionist','Admin'];
                  foreach ($roles as $role) {
                    $selected = $staff['staff_role'] === $role ? 'selected' : '';
                    echo "<option value=\"$role\" $selected>$role</option>";
                  }
                ?>
              </select>
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Phone Number</label>
              <input
                name="phone_number"
                value="<?= htmlspecialchars($staff['phone_number']) ?>"
                class="w-full border rounded-lg p-2"
              >
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Email Address</label>
              <input
                name="email"
                value="<?= htmlspecialchars($staff['email']) ?>"
                class="w-full border rounded-lg p-2"
              >
            </div>

            <div>
              <label class="text-sm font-medium text-[#2F5395]">Status</label>
              <select name="is_active" class="w-full border rounded-lg p-2">
                <option value="1" <?= $staff['is_active'] == 1 ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= $staff['is_active'] == 0 ? 'selected' : '' ?>>Inactive</option>
              </select>
            </div>

            <div class="flex gap-3 pt-4">
              <button
                type="submit"
                name="save_staff"
                class="bg-[#2F5395] text-white px-6 py-2 rounded-lg hover:bg-[#3EDCDE] transition"
              >
                Save Changes
              </button>

              <button
                type="submit"
                name="deactivate_staff"
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
            </div>

          </form>
        </div>
      </div>
    </main>
  </div>

</body>
</html>
