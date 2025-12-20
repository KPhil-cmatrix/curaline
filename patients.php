<?php

session_start();

// We block access if the user is not logged in and require general staff perms
require __DIR__ . "/sessions/auth_staff.php";

include "backend/db.php";

$sql = "select * from patient_info";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Curaline – Patients</title>
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
          class="block py-2 px-4 rounded-lg bg-[#3EDCDE] text-[#F3F6FA] transition text-xl"
          >Patients</a
        >

        <a
          href="staff.php"
          class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition"
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
        <a href="sessions/logout.php"
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
        <h1 class="text-2xl font-semibold text-[#2F5395]">Patients</h1>
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
                Patient ID
              </th>

              <th
                class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]"
              >
                Full Name
              </th>

              <th
                class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]"
              >
                Sex
              </th>

              <th
                class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]"
              >
                Date of Birth
              </th>

              <th
                class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]"
              >
                Phone Number
              </th>

              <th
                class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]"
              >
                Parish
              </th>

              <th
                class="p-3 text-[#2F5395] font-semibold border-b border-[#8FBFE0]"
              >
                Action
              </th>
            </tr>
          </thead>

          <!------------ Table Body Content ------------>

          <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
              <tr>
                <td class="p-3 border-b border-[#E0E3E7]"><?php echo $row['patient_id']; ?></td>
                <td class="p-3 border-b border-[#E0E3E7]"><?php echo $row['first_name']. " " . $row['last_name']; ?></td>
                <td class="p-3 border-b border-[#E0E3E7]"><?php echo $row['sex']; ?></td>
                <td class="p-3 border-b border-[#E0E3E7]"><?php echo $row['date_of_birth']; ?></td>
                <td class="p-3 border-b border-[#E0E3E7]"><?php echo $row['phone_number']; ?></td>
                <td class="p-3 border-b border-[#E0E3E7]"><?php echo $row['parish_of_residence']; ?></td>
                <td class="p-3 border-b border-[#E0E3E7] text-[#3EDCDE] font-semibold hover:underline">
                    <a href="patientInfo.php?patient_id=<?php echo $row['patient_id'];?>" 
                    class=text-[#3EDCDE font-semibold hover:underline>
                      View</a>
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
