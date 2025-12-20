<?php

// We block access if the user is not logged in or not an admin, we require admin privildges from the admins page
require __DIR__ . "/sessions/auth_admin.php";


// Database access

include "backend/db.php";

// Error and success messages

$error = null;
$success = null;

//=====================[ HANDLE CREATE ]=====================\\

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


  if (!isset($_POST['type'])) {
    $error = "Invalid form submission.";
  }

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

      // Variable assingments

      $staff_fname = $_POST['staff_fname'];
      $staff_lname = $_POST['staff_lname'];
      $staff_role = $_POST['staff_role'];
      $staff_phone = $_POST['staff_phone'];
      $staff_email = $_POST['staff_email'];

      // We create staff id

      $prefix = $staff_role === 'Dentist' ? 'DEN' : ($staff_role === 'Nurse' ? 'NUR' : 'STA');

      // Generate ID
      $count = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT COUNT(*) AS c FROM staff_info WHERE staff_role='$staff_role'")
      )['c'] + 1;

      $staff_id = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);

      // SQL Inserts
      if (!$error) {
        $staff_sql = "
        INSERT INTO staff_info 
        (staff_id,
        first_name,
        last_name,
        staff_role,
        phone_number,
        email, 
        is_active)

        VALUES 
        (
        '$staff_id',
        '$staff_fname',
        '$staff_lname',
        '$staff_role',
        '$staff_phone',
        '$staff_email',
        1)
      ";

      if (mysqli_query($conn,$staff_sql)) {
        $success = "Staff member added successfully.";
      } else {
        $error = mysqli_error($conn);
      }
      }
    }
  }

  // Here we ensure that all required input is filled

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
      // Error message telling users to fill all info
      $error = "All patient fields are required.";
    } 
    
    if (!$error) {

      $count = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT COUNT(*) AS c FROM patient_info")
      )['c'] + 1;

      // Generate ID

      $patient_id = 'PAT' . str_pad($count, 4, '0', STR_PAD_LEFT);
      
      // We post all the stored values we got from the form, another way of doing what we did above

      $first_name = $_POST['first_name'];
      $last_name = $_POST['last_name'];
      $dob = $_POST['date_of_birth'];
      $sex = $_POST['sex'];
      $phone = $_POST['phone_number'];
      $email = $_POST['email'];
      $parish = $_POST['parish_of_residence'];

      $em_name = $_POST['emergency_contact_name'];
      $em_phone = $_POST['emergency_contact_phone'];
      $em_relation = $_POST['emergency_contact_relationship'];

      $has_allergies = $_POST['has_allergies'];
      $allergy_details = $_POST['allergy_details'] ?? null;
      
      // If no allergies then we set allergy_details to null so it stays "empty"

      if ($has_allergies==0) {
        $allergy_details=NULL;
      } 
      
      if ($has_allergies == 1 && empty($allergy_details)) {
        $error = "Please specify allergy details.";
      }

      //==========[ SQL INSERTS ]==========\\
        if (!$error) {
        $sql = "
        insert into patient_info(
        patient_id, first_name, last_name, date_of_birth, sex, phone_number, email, parish_of_residence, emergency_contact_name,
        emergency_contact_phone, emergency_contact_relationship, has_allergies, allergy_details, is_active
        ) values (
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
        1 )";

        // ^ Above here we checked if allergy was null and set the details to null if null ^ 

        if (mysqli_query($conn,$sql)) {
          $success = "Patient added successfully.";
        } else {
          $error = mysqli_error($conn);
        }
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Curaline – Admin Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
    
  <body class="flex min-h-screen bg-[#F3F6FA] text-gray-800">

    <!------------ SIDEBAR ------------>
    <aside class="w-64 bg-[#2F5395] text-white flex flex-col">
      <div class="p-6 border-b border-[#3EDCDE]">
        <h1 class="text-3xl font-extrabold tracking-wide">Curaline</h1>
      </div>

      <nav class="flex-1 p-4 space-y-2">
        <a href="dashboard.php"
          class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition">
          Dashboard
        </a>

        <a href="patients.php"
          class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition">
          Patients
        </a>

        <a href="staff.php"
          class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition">
          Staff
        </a>

        <a href="appointments.php"
          class="block py-2 px-4 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition">
          Appointments
        </a>

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

    <!-------------- MAIN CONTENT -------------->
    <div class="flex-1 flex flex-col">

      <!-- Top bar -->
      <header class="bg-white shadow p-4 border-b border-[#E0E3E7]">
        <h1 class="text-2xl font-semibold text-[#2F5395]">Admin Panel</h1>
      </header>

      <!-- Page content -->
      <main class="flex-1 p-6 flex justify-center items-start">

        <div class="bg-white shadow rounded-xl p-6 w-full max-w-xl space-y-6">

          <!-- Here we pass in error and success messages  based on the stored values we made in the PHP section above -->    

          <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded">
              <?= $error ?>
            </div>
          <?php endif; ?>

          <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded">
              <?= $success ?>
            </div>
          <?php endif; ?>

          <form method="POST" class="space-y-4">

            <!-- MAIN DROPDOWN -->
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

            <!-- STAFF FORM -->
            <div id="staffForm" class="hidden space-y-3">
              <div>
                <label class="text-sm font-medium text-[#2F5395]">First Name</label>
                <input 
                  name="staff_fname" 
                  placeholder="First Name" 
                  class="w-full border rounded-lg p-2"
                  >
              </div>

              <div>
                <label class="text-sm font-medium text-[#2F5395]">Last Name</label>
                <input 
                  name="staff_lname" 
                  placeholder="Last Name" 
                  class="w-full border rounded-lg p-2">
              </div>

              <!-- Staff role -->

              <div>
                <label class="text-sm font-medium text-[#2F5395]">Staff Role</label>
                <select 
                  name="staff_role" 
                  class="w-full border rounded-lg p-2">
                  <option value="">Select Role</option>
                  <option value="Dentist">Dentist</option>
                  <option value="Nurse">Nurse</option>
                  <option value="Receptionist">Receptionist</option>
                  <option value="Admin">Admin</option>
                </select>
              </div>

              <!-- Phone Number -->

              <div>
                <label class="text-sm font-medium text-[#2F5395]">Phone Number</label>
                <input 
                name="staff_phone" 
                placeholder="876-453-2354" 
                class="w-full border rounded-lg p-2">
              </div>

              <!-- Email -->

              <div>
                <label class="text-sm font-medium text-[#2F5395]">Email Address</label>
                <input 
                name="staff_email" 
                placeholder="example@gmail.com" 
                class="w-full border rounded-lg p-2">
              </div>

            </div>
            
            <!------------ PATIENT FORM ------------>

            <div id="patientForm" class="hidden space-y-3">

              <!-- First and Last Names -->

              <div>
                <label class="text-sm font-medium text-[#2F5395]">First Name</label>
                <input type="text"
                name="first_name"
                class="w-full border border-[#8FBFE0] rounded-lg p-2"
                placeholder="Enter first name"
                />
              </div>

              <div>
                <label class="text-sm font-medium text-[#2F5395]">Last Name</label>
                <input type="text"
                name="last_name"
                class="w-full border border-[#8FBFE0] rounded-lg p-2"
                placeholder="Enter last name"
                />
              </div>

              <!-- Sex -->
              <div>
                <label class="text-sm font-medium text-[#2F5395]">Sex</label>
                  <select   
                    name="sex"
                    class = "w-full border border-[#8FBFE0] rounded-lg p-2"
                  >
                  <option value="">Select</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                  <option value="Other">Other</option>
                  </select>
                </label>
              </div>
            
              <!-- Date of Birth -->

              <div>
                <label class="text-sm font-medium text-[#2F5395]">Date of Birth</label>
                <input type="date" 
                  name="date_of_birth" 
                  class="w-full border border-[#8FBFE0] rounded-lg p-2"
                />
              </div>

              <!-- Phone Number -->

              <div>
                <label class="text-sm font-medium text-[#2F5395]">Phone Number</label>
                  <input 
                    type="text"
                    name="phone_number"
                    class="w-full border border-[#8FBFE0] rounded-lg p-2"
                    placeholder="e.g 876-555-1234"
                  />
              </div>

              <!-- Email Address -->
              
              <div>
                <label class="text-sm font-medium text-[#2F5395]">Email</label>
                <input type="email"
                name="email"
                class="w-full border border-[#8FBFE0] rounded-lg p-2"
                placeholder="example@email.com"
                >
              </div>

              <!-- Parishes -->

              <div>
                <label class="text-sm font-medium text-[#2F5395]">Parish</label>
                  <select 
                  name="parish_of_residence"
                  class="w-full border border-[#8FBFE0] rounded-lg p-2"
                  >
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
                    <option>St. mary</option>
                    <option>Portland</option>
                    <option>St. Thomas</option>
                  </select>
              </div>

              <!-- Allergies -->

              <div>
                <label class="text-sm font-medium text-[#2F5395]" >Allergies</label>

                <select 
                name="has_allergies"
                class="w-full border border-[#8FBFE0] rounded-lg p-2"
                >
                <option value="">Select</option>
                <option value="0">No</option>
                <option value="1">Yes</option>
                </select>
              </div>
              <div>
                <label class="text-sm font-medium text-[#2F5395]">Allergy Details (if any)</label>
                <input 
                type="text"
                name="allergy_details"
                placeholder="e.g Penincillin, Latex"
                class="w-full border border-[#8FBFE0] rounded-lg p-2"
                >
              </div>

              <div>
                <label class="text-sm font-medium text-[#2F5395]">Emergency Contact Name</label>
                <input 
                type="text"
                name="emergency_contact_name"
                placeholder="Full name"
                class="w-full border border-[#8FBFE0] rounded-lg p-2"
                />
              </div>

              <div>
                <label class="text-sm font-medium text-[#2F5395]">Emergency Contact Phone</label>
                <input 
                type="text"
                name="emergency_contact_phone"
                placeholder="e.g 876-555-1234"
                class="w-full border border-[#8FBFE0] rounded-lg p-2"
                />
              </div>
              <div>
                <label class="text-sm font-medium text-[#2F5395]">Emergency Contact Relation</label>
                <select 
                name="emergency_contact_relationship"
                class="w-full border border-[#8FBFE0] rounded-lg p-2"
                >
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

            <!-- SUBMIT -->
            <button id="submitButton"
              class="bg-[#2F5395] text-white w-full py-2 rounded-lg hover:bg-[#3EDCDE] transition">
              Create
            </button>
          </form>
        </div>
    </div>

    <script>
      //===================[ We create a script to disable the forms currently not in use ]===================\\
      function toggleForms() {
        const type = document.getElementById('typeSelect').value;
        const staff = document.getElementById('staffForm');
        const patient = document.getElementById('patientForm');

        // Disable all inputs first
        staff.querySelectorAll('input, select').forEach(el => el.disabled = true);
        patient.querySelectorAll('input, select').forEach(el => el.disabled = true);
        

        // Hide both forms
        staff.classList.add('hidden');
        patient.classList.add('hidden');

        // Depending on what's picked we unhide the form and enable the input
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