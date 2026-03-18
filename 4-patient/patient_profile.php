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
    patient_id,
    first_name,
    last_name,
    sex,
    date_of_birth,
    phone_number,
    email,
    parish_of_residence,
    emergency_contact_name,
    emergency_contact_phone,
    emergency_contact_relationship,
    has_allergies,
    allergy_details
  from patient_info
  where patient_id = '$patient_id'
  limit 1
";

$result = mysqli_query($conn, $sql);
$patient = ($result && mysqli_num_rows($result) === 1) ? mysqli_fetch_assoc($result) : null;

function h($v) {
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Curaline – My Profile</title>
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
      <a href="patient_appointments.php" class="block py-2 px-4 rounded-lg hover:bg-white/10 text-lg">
        My Appointments
      </a>
      <a href="patient_profile.php" class="block py-2 px-4 rounded-lg bg-[#3EDCDE] text-[#F3F6FA] text-xl transition">
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
      <h1 class="text-2xl font-semibold text-[#2F5395]">My Profile</h1>
      <span class="text-[#9FA2B2] font-medium">
        Patient • <?= h($_SESSION['user_id']) ?>
      </span>
    </header>

    <main class="flex-1 p-6 space-y-6">
      <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold text-[#2F5395] mb-4">Patient Information</h3>

        <?php if (!$patient): ?>
          <p class="text-[#9FA2B2]">Profile not found.</p>
        <?php else: ?>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <div>
              <p class="text-sm text-[#9FA2B2]">Full Name</p>
              <p class="font-semibold"><?= h($patient['first_name']) ?> <?= h($patient['last_name']) ?></p>
            </div>

            <div>
              <p class="text-sm text-[#9FA2B2]">Sex</p>
              <p class="font-semibold"><?= h($patient['sex']) ?></p>
            </div>

            <div>
              <p class="text-sm text-[#9FA2B2]">Date of Birth</p>
              <p class="font-semibold"><?= h($patient['date_of_birth']) ?></p>
            </div>

            <div>
              <p class="text-sm text-[#9FA2B2]">Parish of Residence</p>
              <p class="font-semibold"><?= h($patient['parish_of_residence']) ?></p>
            </div>

            <div>
              <p class="text-sm text-[#9FA2B2]">Phone Number</p>
              <p class="font-semibold"><?= h($patient['phone_number']) ?></p>
            </div>

            <div>
              <p class="text-sm text-[#9FA2B2]">Email</p>
              <p class="font-semibold"><?= h($patient['email']) ?></p>
            </div>

            <div class="md:col-span-2">
              <p class="text-sm text-[#9FA2B2]">Allergies</p>
              <p class="font-semibold">
                <?= ((int)$patient['has_allergies'] === 1) ? "Yes" : "No" ?>
                <?php if (!empty($patient['allergy_details'])): ?>
                  — <?= h($patient['allergy_details']) ?>
                <?php endif; ?>
              </p>
            </div>

            <div class="md:col-span-2">
              <p class="text-sm text-[#9FA2B2]">Emergency Contact</p>
              <p class="font-semibold">
                <?= h($patient['emergency_contact_name']) ?>
                (<?= h($patient['emergency_contact_relationship']) ?>) —
                <?= h($patient['emergency_contact_phone']) ?>
              </p>
            </div>

          </div>

        <?php endif; ?>

      </div>
    </main>
  </div>

</body>
</html>