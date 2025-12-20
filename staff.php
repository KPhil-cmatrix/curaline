<?php

// We block access if the user is not logged in and require general staff perms
require __DIR__ . "/sessions/auth_staff.php";

include "backend/db.php";


if (
  !isset($_SESSION['logged_in']) ||
  $_SESSION['staff_role'] === 'Patient'
) {
  header("Location: login.php");
  exit;
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
          <a href="admin.php"
            class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition">
            Admin
          </a>
        <?php endif; ?>
      </nav>

      <div class="p-4 mt-auto">
        <a href="curaline/sessions/logout.php"
          class="w-full block text-center bg-[#9FA2B2] py-2 rounded-lg font-semibold"
          >
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
                <td class="p-3 border-b border-[#E0E3E7]"><?php echo $row['phone_number']; ?></td>
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
