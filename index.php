<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Curaline – Clinic Management System</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="1-assets/ui.css">
</head>

<body class="bg-gradient-to-br from-[#EEF3FA] to-[#C9D8F0] text-gray-800">

  <!-- NAVBAR -->
  <header class="flex justify-between items-center px-8 py-5 bg-white shadow-sm">
    <div class="flex items-center gap-3">
      <img src="1-assets/curalineBlueLogo.png" class="h-10">
      <span class="text-xl font-bold text-[#2F5395]">Curaline</span>
    </div>

    <a href="login_select.php"
      class="bg-[#2F5395] text-white px-6 py-2 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition">
      Login
    </a>
  </header>

  <!-- HERO -->
  <section class="text-center py-20 px-6">
    <h1 class="text-4xl md:text-5xl font-bold text-[#2F5395] mb-6">
      Modern Clinic Management Made Simple
    </h1>

    <p class="text-lg text-gray-600 max-w-2xl mx-auto mb-8">
      Curaline is a web-based clinic queue and appointment management system designed
      to streamline patient scheduling, staff coordination, and clinic operations.
    </p>

    <a href="login_select.php"
      class="bg-[#3EDCDE] text-[#2F5395] px-8 py-3 rounded-xl font-semibold shadow-md hover:opacity-90 transition">
      Get Started
    </a>
  </section>

  <!-- FEATURES -->
  <section class="px-8 pb-20 max-w-6xl mx-auto grid md:grid-cols-3 gap-6">

    <div class="app-card p-6 text-center">
      <h3 class="text-lg font-semibold text-[#2F5395] mb-2">
        Appointment Management
      </h3>
      <p class="text-gray-500 text-sm">
        Easily schedule, approve, and manage appointments for patients in real time.
      </p>
    </div>

    <div class="app-card p-6 text-center">
      <h3 class="text-lg font-semibold text-[#2F5395] mb-2">
        Patient Records
      </h3>
      <p class="text-gray-500 text-sm">
        Store and access patient information securely with quick retrieval and updates.
      </p>
    </div>

    <div class="app-card p-6 text-center">
      <h3 class="text-lg font-semibold text-[#2F5395] mb-2">
        Staff Coordination
      </h3>
      <p class="text-gray-500 text-sm">
        Manage staff roles, schedules, and responsibilities efficiently.
      </p>
    </div>

  </section>

  <!-- ABOUT -->
  <section class="bg-white py-16 px-6 text-center">
    <h2 class="text-2xl font-bold text-[#2F5395] mb-4">
      About Curaline
    </h2>

    <p class="max-w-3xl mx-auto text-gray-600">
      Curaline was developed as a clinic management solution to improve efficiency in healthcare environments.
      The system allows administrators, staff, and patients to interact through a centralized platform,
      ensuring better communication, reduced waiting times, and improved patient care.
    </p>
  </section>

  <!-- FOOTER -->
  <footer class="text-center py-6 text-sm text-gray-500">
    © <?= date('Y') ?> Curaline. All rights reserved.
  </footer>

</body>
</html>