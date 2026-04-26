<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Curaline – Clinic Queue & Appointment Management System</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="1-assets/ui.css">
</head>

<body class="bg-gradient-to-br from-[#EEF3FA] to-[#C9D8F0] text-gray-800">

  <!-- NAVBAR -->
  <header class="flex justify-between items-center px-8 py-5 bg-white shadow-sm">
    <div class="flex items-center gap-3">
      <img src="1-assets/CuralineBlueLogo.png" class="h-10" alt="Curaline Logo">
      <span class="text-xl font-bold text-[#2F5395]">Curaline</span>
    </div>

    <a href="login_select.php"
      class="bg-[#2F5395] text-white px-6 py-2 rounded-lg hover:bg-[#3EDCDE] hover:text-[#2F5395] transition">
      Login
    </a>
  </header>

  <!-- HERO -->
  <section class="px-8 py-20 max-w-6xl mx-auto grid md:grid-cols-2 gap-10 items-center">
    <div>
      <p class="text-sm font-semibold text-[#3EDCDE] mb-3 uppercase tracking-wide">
        Clinic Queue & Appointment Management
      </p>

      <h1 class="text-4xl md:text-5xl font-bold text-[#2F5395] mb-6 leading-tight">
        A smarter way to manage clinic appointments and patient flow.
      </h1>

      <p class="text-lg text-gray-600 mb-8">
        Curaline helps clinics organise appointments, manage patient requests,
        monitor scheduling activity, and improve communication between patients,
        staff, and administrators.
      </p>

      <div class="flex flex-wrap gap-4">
        <a href="login.php?role=patient"
          class="bg-[#3EDCDE] text-[#2F5395] px-8 py-3 rounded-xl font-semibold shadow-md hover:opacity-90 transition">
          Patient Login
        </a>

        <a href="login.php?role=staff"
          class="bg-[#2F5395] text-white px-8 py-3 rounded-xl font-semibold shadow-md hover:bg-[#26457C] transition">
          Staff Login
        </a>
      </div>
    </div>

    <div class="app-card p-8">
      <h2 class="text-2xl font-bold text-[#2F5395] mb-4">
        Built for structured clinic workflows
      </h2>

      <p class="text-gray-600 mb-6">
        From appointment requests to staff approval, Curaline supports a guided
        workflow that helps reduce confusion and improve day-to-day clinic operations.
      </p>

      <div class="space-y-3 text-sm text-gray-600">
        <p>✔ Appointment requests and approvals</p>
        <p>✔ Rescheduling and cancellation requests</p>
        <p>✔ Patient notifications</p>
        <p>✔ Reports, printing, and CSV export</p>
      </div>
    </div>
  </section>

  <!-- FEATURES -->
  <section class="px-8 pb-20 max-w-6xl mx-auto grid md:grid-cols-4 gap-6">

    <div class="app-card p-6">
      <h3 class="text-lg font-semibold text-[#2F5395] mb-2">
        Appointment Management
      </h3>
      <p class="text-gray-500 text-sm">
        Patients can request appointments while staff approve, decline, or update appointment details.
      </p>
    </div>

    <div class="app-card p-6">
      <h3 class="text-lg font-semibold text-[#2F5395] mb-2">
        Patient Updates
      </h3>
      <p class="text-gray-500 text-sm">
        Patients receive in-system notifications when important appointment updates occur.
      </p>
    </div>

    <div class="app-card p-6">
      <h3 class="text-lg font-semibold text-[#2F5395] mb-2">
        Reports & Analytics
      </h3>
      <p class="text-gray-500 text-sm">
        Staff can filter, print, and export appointment activity for review and decision-making.
      </p>
    </div>

    <div class="app-card p-6">
      <h3 class="text-lg font-semibold text-[#2F5395] mb-2">
        Doctor Notes
      </h3>
      <p class="text-gray-500 text-sm">
        Completed appointments can include outcome notes and recommendations for patient review.
      </p>
    </div>

  </section>

  <!-- ABOUT -->
  <section class="bg-white py-16 px-6">
    <div class="max-w-5xl mx-auto grid md:grid-cols-2 gap-10 items-start">
      <div>
        <h2 class="text-3xl font-bold text-[#2F5395] mb-4">
          About Curaline
        </h2>

        <p class="text-gray-600 leading-relaxed">
          Curaline is a web-based Clinic Queue and Appointment Management System
          designed for structured clinical environments. It supports appointment
          booking, rescheduling requests, cancellation requests, staff approvals,
          patient notifications, and appointment reporting.
        </p>
      </div>

      <div class="bg-[#EEF3FA] rounded-2xl p-6 border border-[#D6E2F0]">
        <h3 class="text-xl font-bold text-[#2F5395] mb-3">
          Privacy-focused access
        </h3>

        <p class="text-gray-600 text-sm leading-relaxed">
          Curaline uses role-based access to ensure that patients, staff, and
          administrators only access the features relevant to their role. Patient
          information is handled with privacy and security in mind.
        </p>
      </div>
    </div>
  </section>

  <!-- USER ROLES -->
  <section class="px-8 py-16 max-w-6xl mx-auto">
    <h2 class="text-3xl font-bold text-[#2F5395] text-center mb-10">
      Designed for every clinic role
    </h2>

    <div class="grid md:grid-cols-3 gap-6">
      <div class="app-card p-6">
        <h3 class="font-bold text-[#2F5395] mb-2">Patients</h3>
        <p class="text-sm text-gray-500">
          Request appointments, submit reschedule or cancellation requests, view appointment history, and receive updates.
        </p>
      </div>

      <div class="app-card p-6">
        <h3 class="font-bold text-[#2F5395] mb-2">Staff</h3>
        <p class="text-sm text-gray-500">
          Manage appointment requests, update statuses, add appointment notes, and review reports.
        </p>
      </div>

      <div class="app-card p-6">
        <h3 class="font-bold text-[#2F5395] mb-2">Administrators</h3>
        <p class="text-sm text-gray-500">
          Manage users, configure clinic settings, and oversee system operations.
        </p>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="text-center py-6 text-sm text-gray-500">
    © <?= date('Y') ?> The Curaline Group. All rights reserved.
  </footer>

</body>
</html>