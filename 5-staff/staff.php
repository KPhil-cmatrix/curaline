<?php

/*
- System Name: Curaline Clinic Appointment and Patient Management System (Curaline)
- Developers: Khalia Phillips, Havon James, and Tarik Wilson
- Version: V2.2
- Version Date: Dec 15, 2025
- Purpose of File: Displays general staff data
*/


// We block access if the user is not logged in and require general staff perms
require __DIR__ . '/../3-sessions/auth_staff.php';

include __DIR__ . '/../2-backend/db.php';


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

// Get staff query

$staff_query = "select * from staff_info";

$staff_result = mysqli_query($conn, $staff_query);

if (!$staff_result) {
  die("Doctors data could not be found");
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Curaline – Staff</title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>

  <body class="flex min-h-screen bg-[#F3F6FA] text-gray-800">

    <!------------ Sidebar ------------>
    
    <aside class="w-64 bg-[#2F5395] text-white flex flex-col">
      <div class="p-6 border-b border-[#3EDCDE] flex justify-center">
        <img src="../1-assets/curalineWhiteLogo.png" alt="Curaline" class="h-12 w-auto">
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
          class="block py-2 px-4 rounded-lg bg-[#3EDCDE] text-[#F3F6FA] transition text-xl"
          >Staff</a
        >

        <a
          href="appointments.php"
          class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition"
          >Appointments</a
        >

        <!-- We limit admin section to those with the admin role only -->

        <?php if ($_SESSION['staff_role'] === 'Admin'): ?>
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

    <!------------ Main content ------------>

    <div class="flex-1 flex flex-col">
      <!------------ Top bar ------------>
      <header
        class="bg-white shadow p-4 flex justify-between items-center border-b border-[#E0E3E7]"
      >
        <h1 class="text-2xl font-semibold text-[#2F5395]">Staff</h1>
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

      <!------------ Page Content ------------>
      <div class="p-6 space-y-6">

        <!------------ Content Table ------------>

        <table class="w-full border-collapse text-left">
          <thead>
            <tr>
              <th
                class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]"
              >
                Staff ID
              </th>

              <th
                class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]"
              >
                Full Name
              </th>

              <th
                class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]"
              >
                Staff Role
              </th>

              <th
                class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]"
              >
                Email
              </th>

              <th
                class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]"
              >
                Phone Number
              </th>

              <th
                class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]"
              >
                Is Active
              </th>
            </tr>
          </thead>

          <!------------ Table Body Content ------------>

          <tbody>
            <?php while ($row = mysqli_fetch_assoc($staff_result)) { ?>
              <tr>
                <td class="p-3 border-b border-[#E0E3E7]"><?php echo $row['staff_id']; ?></td>
                <td class="p-3 border-b border-[#E0E3E7]"><?php echo $row['first_name']. " " . $row['last_name']; ?></td>
                <td class="p-3 border-b border-[#E0E3E7]"><?php echo $row['staff_role']; ?></td>
                <td class="p-3 border-b border-[#E0E3E7]"><?php echo $row['email']; ?></td>
                <td class="p-3 border-b border-[#E0E3E7]"><?php echo format_phone($row['phone_number']); ?></td>
                <td class="p-3 border-b border-[#E0E3E7]">
                  <?php 
                    if ($row['is_active'] == 1) {
                      echo 'Yes';
                      } else{
                        echo 'No';
                      }
                  ?>
                </td>
              </tr>
            <?php } ?>
          </tbody>
          <!------------ End of Table body ------------>
        </table>
      </div>
    </div>
  </body>
</html>
