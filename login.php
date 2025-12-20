<?php

if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['logged_in'])) {
  header("Location: dashboard.php");
  exit;
}

echo "LOGIN PAGE HIT<br>";

session_start();

echo "DB CONNECTED<br>";

// we generate error variable for use later
$error = null;

// we get the login role from page 1
$login_role = $_GET['role'] ?? '';

// We block access if the page is opened incorrectly
if (!$login_role || !in_array($login_role, ['staff', 'patient'])) {
  die("Invalid login access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  include "backend/db.php";

  // Create the variables for logging in
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  // Require all fields
  if (!$username || !$password) {
    $error = "All fields are required";
  } 
  else {

    //=====================[ STAFF LOGIN ]=====================\\

    if ($login_role === 'staff') {

      $sql = "
        select
          sa.staff_id,
          sa.password_hash,
          si.staff_role,
          si.first_name
        from staff_auth sa
        join staff_info si on sa.staff_id = si.staff_id
        where sa.username = '$username'
        and sa.is_active = 1
        and si.is_active = 1
        limit 1
      ";

      $result = mysqli_query($conn, $sql);

      if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        if (hash('sha256', $password) !== $user['password_hash']) {
          $error = "Invalid credentials.";
        } else {

          // Create staff session
          $_SESSION['user_id'] = $user['staff_id'];
          $_SESSION['first_name'] = $user['first_name'];
          $_SESSION['staff_role'] = $user['staff_role'];
          $_SESSION['logged_in'] = true;

          // Redirect based on staff role
          if ($user['staff_role'] === 'Admin') {
            header("Location: dashboard.php");
          } else {
            header("Location: staff.php");
          }
          exit;
        }
      }
      else {
        $error = "Invalid credentials.";
      }
    }

    //=====================[ PATIENT LOGIN ]=====================\\

    if ($login_role === 'patient') {
      $error = "Patient login not implemented yet.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Clinic System Mockup</title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body
    class="bg-[#8FBFE0] flex items-center justify-center min-h-screen text-gray-800"
  >
    <section class="bg-white rounded-xl shadow-lg p-8 w-full max-w-md">
      <!-- Header -->
      <h1 class="text-3xl font-extrabold text-center text-[#2F5395] mb-6">
        <?=ucfirst($login_role) ?> Login
      </h1>

      <!-- We print errors here -->

      <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4">
          <?= $error ?>
        </div>
      <?php endif; ?>


      <!-- Form -->
      <!-- We submit the form with the login role pushed so it doesn't endlessly refresh. -->
      <form method="POST" action="login.php?role=<?= $login_role ?>" class="space-y-4">

        <!-- Username -->
        <div>
          <label class="text-sm text-gray-600">Username</label>
          <input
            type="text"
            name="username"
            required
            class="w-full border rounded-lg p-2"
            placeholder="Enter username"
          />
        </div>

        <!-- Password -->
        <div>
          <label class="text-sm text-gray-600">Password</label>
          <input
            type="password"
            name="password"
            required
            class="w-full border rounded-lg p-2"
            placeholder="Enter password"
          />
        </div>

        <!-- Submit -->
        <button
          type="submit"
          class="w-full bg-[#2F5395] text-white py-2 rounded-lg hover:bg-[#3EDCDE]"
        >
          Login
        </button>

      </form>

      <!-- Footer -->
      <p class="text-center text-sm text-[#9FA2B2] mt-6">
        Forgot your password?
        <a
          href="https://www.youtube.com/watch?v=xvFZjo5PgG0&list=RDxvFZjo5PgG0&start_radio=1"
          class="text-[#3EDCDE] font-medium hover:underline"
          >Reset here</a
        >
      </p>
    </section>
  </body>
</html>