<?php

/*
- System Name: Curaline Clinic Appointment and Patient Management System (Curaline)
- Developers: Khalia Phillips, Havon James, and Tarik Wilson
- Version: V2.2
- Version Date: Dec 15, 2025
- Purpose of File: Dashboard file that displasy general data for the system
*/

//=====================[ ACCESS CONTROL ]=====================\\

require __DIR__ . '/../3-sessions/auth_staff.php';

//=====================[ DATABASE ACCESS ]=====================\\

include __DIR__ . '/../2-backend/db.php';

//=====================[ REPORT FILTERS ]=====================\\

// Filter values
$filter_doctor = trim($_GET['doctor'] ?? '');
$filter_patient = trim($_GET['patient'] ?? '');
$filter_status = trim($_GET['status'] ?? '');
$filter_receptionist = trim($_GET['receptionist'] ?? '');
$filter_date = trim($_GET['date'] ?? '');

// Pagination
$records_per_page = 15;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Dropdown data - doctors
$doctor_list = [];
$doctor_sql = "SELECT staff_id, first_name, last_name, staff_role
               FROM staff_info
               WHERE is_active = 1
               AND (
                    staff_role = 'Doctor'
                    OR staff_role = 'Dentist'
                    OR staff_role LIKE '%Doctor%'
                    OR staff_role LIKE '%Dentist%'
               )
               ORDER BY first_name, last_name";
$doctor_result = mysqli_query($conn, $doctor_sql);
while ($row = mysqli_fetch_assoc($doctor_result)) {
    $doctor_list[] = $row;
}

// Dropdown data - receptionists / staff
$receptionist_list = [];
$receptionist_sql = "SELECT staff_id, first_name, last_name, staff_role
                     FROM staff_info
                     WHERE is_active = 1
                     ORDER BY first_name, last_name";
$receptionist_result = mysqli_query($conn, $receptionist_sql);
while ($row = mysqli_fetch_assoc($receptionist_result)) {
    $receptionist_list[] = $row;
}

// Dropdown data - patients
$patient_list = [];
$patient_sql = "SELECT patient_id, first_name, last_name
                FROM patient_info
                WHERE is_active = 1
                ORDER BY first_name, last_name";
$patient_result = mysqli_query($conn, $patient_sql);
while ($row = mysqli_fetch_assoc($patient_result)) {
    $patient_list[] = $row;
}

// Base report query
$report_base_sql = "
    FROM appointments a
    LEFT JOIN staff_info d 
        ON a.dentist_id = d.staff_id
    LEFT JOIN patient_info p 
        ON a.patient_id = p.patient_id
    LEFT JOIN staff_info r 
        ON a.booked_by_staff_id = r.staff_id
    WHERE 1=1
";

$report_params = [];
$report_types = "";

if ($filter_doctor !== '') {
    $report_base_sql .= " AND a.dentist_id = ? ";
    $report_params[] = $filter_doctor;
    $report_types .= "s";
}

if ($filter_patient !== '') {
    $report_base_sql .= " AND a.patient_id = ? ";
    $report_params[] = $filter_patient;
    $report_types .= "s";
}

if ($filter_status !== '') {
    $report_base_sql .= " AND a.status = ? ";
    $report_params[] = $filter_status;
    $report_types .= "s";
}

if ($filter_receptionist !== '') {
    $report_base_sql .= " AND a.booked_by_staff_id = ? ";
    $report_params[] = $filter_receptionist;
    $report_types .= "s";
}

if ($filter_date !== '') {
    $report_base_sql .= " AND DATE(a.scheduled_datetime) = ? ";
    $report_params[] = $filter_date;
    $report_types .= "s";
}

// Count total filtered records
$count_sql = "SELECT COUNT(*) AS total " . $report_base_sql;
$count_stmt = mysqli_prepare($conn, $count_sql);

if ($count_stmt) {
    if (!empty($report_params)) {
        mysqli_stmt_bind_param($count_stmt, $report_types, ...$report_params);
    }
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $total_records = mysqli_fetch_assoc($count_result)['total'] ?? 0;
} else {
    die("Count query preparation failed: " . mysqli_error($conn));
}

$total_pages = max(1, ceil($total_records / $records_per_page));

// Main paginated report query
$report_sql = "
    SELECT 
        a.appointment_id,
        a.dental_service_type,
        a.scheduled_datetime,
        a.status,

        d.first_name AS doctor_first_name,
        d.last_name AS doctor_last_name,

        p.first_name AS patient_first_name,
        p.last_name AS patient_last_name,

        r.first_name AS receptionist_first_name,
        r.last_name AS receptionist_last_name,
        r.staff_role AS receptionist_role
    " . $report_base_sql . "
    ORDER BY a.scheduled_datetime DESC
    LIMIT ? OFFSET ?
";

$report_stmt = mysqli_prepare($conn, $report_sql);

if ($report_stmt) {
    $final_params = $report_params;
    $final_types = $report_types . "ii";
    $final_params[] = $records_per_page;
    $final_params[] = $offset;

    mysqli_stmt_bind_param($report_stmt, $final_types, ...$final_params);
    mysqli_stmt_execute($report_stmt);
    $report_result = mysqli_stmt_get_result($report_stmt);
} else {
    die("Report query preparation failed: " . mysqli_error($conn));
}

// END OF REPORT FILTERS //

error_reporting(E_ALL);
ini_set('display_errors', 1);

// We get the total of active patients

$patients_sql = "select count(*) as total from patient_info where is_active=1";
$patients_result = mysqli_query($conn, $patients_sql);
$patients_data = mysqli_fetch_assoc($patients_result);
$active_patients = $patients_data['total'];

// We get the appointments for the day
$today = date('Y-m-d');
$app_sql = "select count(*) as total from appointments where date(scheduled_datetime) = '$today'";
$app_result = mysqli_query($conn, $app_sql);
$appointments_today = mysqli_fetch_assoc($app_result)['total'];

// We get the current total appointments

$app_count_sql = "select count(*) as total from appointments";
$app_count_result = mysqli_query($conn, $app_count_sql);
$app_count_total = mysqli_fetch_assoc($app_count_result)['total'];

// We get the SQL Querries for each item

// Pending Count
$pending_query = "SELECT COUNT(*) AS total FROM appointments WHERE status = 'Pending'";
$pending_result = mysqli_query($conn, $pending_query);
$pending_count = mysqli_fetch_assoc($pending_result)['total'] ?? 0;

// Completed Count
$completed_query = "SELECT COUNT(*) AS total FROM appointments WHERE status = 'Completed'";
$completed_result = mysqli_query($conn, $completed_query);
$completed_count = mysqli_fetch_assoc($completed_result)['total'] ?? 0;

// Top Service
$service_query = "
  SELECT dental_service_type, COUNT(*) AS total
  FROM appointments
  GROUP BY dental_service_type
  ORDER BY total DESC
  LIMIT 1
";
$service_result = mysqli_query($conn, $service_query);
$service_row = mysqli_fetch_assoc($service_result);

$top_service = $service_row['dental_service_type'] ?? 'N/A';

 // PRINTING LOGIC FOR THE REPORT SYSTEM  // ---

if (isset($_GET['export']) && $_GET['export'] === 'csv') {

    $export_sql = "
        SELECT 
            a.appointment_id,
            CONCAT(COALESCE(d.first_name, ''), ' ', COALESCE(d.last_name, '')) AS doctor_name,
            CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.last_name, '')) AS patient_name,
            a.dental_service_type,
            a.scheduled_datetime,
            a.status,
            CONCAT(COALESCE(r.first_name, ''), ' ', COALESCE(r.last_name, '')) AS booked_by
        " . $report_base_sql . "
        ORDER BY a.scheduled_datetime DESC
    ";

    $export_stmt = mysqli_prepare($conn, $export_sql);

    if ($export_stmt) {
        if (!empty($report_params)) {
            mysqli_stmt_bind_param($export_stmt, $report_types, ...$report_params);
        }
        mysqli_stmt_execute($export_stmt);
        $export_result = mysqli_stmt_get_result($export_stmt);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="curaline_appointment_report.csv"');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'Appointment ID',
            'Doctor',
            'Patient',
            'Service',
            'Scheduled Date & Time',
            'Status',
            'Booked By'
        ]);

        while ($row = mysqli_fetch_assoc($export_result)) {
            fputcsv($output, [
                $row['appointment_id'],
                trim($row['doctor_name']),
                trim($row['patient_name']),
                $row['dental_service_type'],
                $row['scheduled_datetime'],
                $row['status'],
                trim($row['booked_by'])
            ]);
        }

        fclose($output);
        exit;
    } else {
        die("Export query preparation failed: " . mysqli_error($conn));
    }
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Curaline Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../1-assets/ui.css">
  </head>

  <body class="flex min-h-screen bg-gradient-to-br from-[#EEF3FA] to-[#C9D8F0] text-gray-800">
    <aside class="w-64 bg-gradient-to-b from-[#2F5395] to-[#26457C] text-white flex flex-col shadow-xl sticky top-0 h-screen">
  
      <!-- Logo -->
      <div class="px-6 py-6 border-b border-white/10 flex items-center justify-center">
        <img src="../1-assets/curalineWhiteLogo.png" alt="Curaline" class="h-12 w-auto">
      </div>

      <!-- Navigation -->
      <nav class="flex-1 p-4 space-y-2">

        <!-- Dashboard -->
        <a href="dashboard.php"
          class="flex items-center gap-3 px-4 py-3 rounded-xl bg-[#3EDCDE] text-[#2F5395] font-medium shadow-md transition-all duration-200">
          
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 3v18h18M9 17V9m4 8V5m4 12v-4" />
          </svg>

          <span>Dashboard</span>
        </a>

        <!-- Patients -->
        <a href="patients.php"
          class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-all duration-200">
          
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M16 14a4 4 0 10-8 0m8 0a4 4 0 10-8 0m8 0v1a2 2 0 002 2h1m-11-3v1a2 2 0 01-2 2H4" />
          </svg>

          <span>Patients</span>
        </a>

        <!-- Staff -->
        <a href="staff.php"
          class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-all duration-200">
          
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M5.121 17.804A7.969 7.969 0 0112 15c2.136 0 4.07.84 5.879 2.204M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>

          <span>Staff</span>
        </a>

        <!-- Appointments -->
        <a href="appointments.php"
          class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-all duration-200">
          
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10m-11 8h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>

          <span>Appointments</span>
        </a>

        <!-- Admin -->
        <?php if ($_SESSION['staff_role'] === 'Admin'): ?>
          <a href="../6-admin/admin.php"
            class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition-all duration-200">
            
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M11.983 5.176a2 2 0 012.034 0l1.607.93a2 2 0 001.518.18l1.78-.48a2 2 0 012.452 2.452l-.48 1.78a2 2 0 00.18 1.518l.93 1.607a2 2 0 010 2.034l-.93 1.607a2 2 0 00-.18 1.518l.48 1.78a2 2 0 01-2.452 2.452l-1.78-.48a2 2 0 00-1.518.18l-1.607.93a2 2 0 01-2.034 0l-1.607-.93a2 2 0 00-1.518-.18l-1.78.48a2 2 0 01-2.452-2.452l.48-1.78a2 2 0 00-.18-1.518l-.93-1.607a2 2 0 010-2.034l.93-1.607a2 2 0 00.18-1.518l-.48-1.78a2 2 0 012.452-2.452l1.78.48a2 2 0 001.518-.18l1.607-.93z" />
            </svg>

            <span>Admin</span>
          </a>
        <?php endif; ?>

      </nav>

      <!-- Logout -->
      <div class="p-4 border-t border-white/10 space-y-3">

      <!-- User info -->
      <div class="flex items-center gap-3 px-2">
        <div class="w-10 h-10 rounded-full bg-[#3EDCDE] flex items-center justify-center font-bold">
          <?= strtoupper(substr($_SESSION['staff_role'], 0, 1)) ?>
        </div>
        <div class="text-sm">
          <p class="font-medium"><?= $_SESSION['staff_role'] ?></p>
          <p class="text-xs text-white/60">ID: <?= $_SESSION['user_id'] ?></p>
        </div>
      </div>

      <!-- Logout -->
      <a href="../3-sessions/logout.php"
        class="block w-full text-center bg-white/20 hover:bg-white/30 transition py-2 rounded-xl text-sm font-semibold">
        Logout
      </a>

    </div>
    </aside>

  <!-- Main content -->
    <div class="flex-1 p-6 space-y-6 max-w-6xl">
      <!-- Top bar -->
      <header class="app-card p-6 flex items-center justify-between mb-6">

        <!--CODE BORDER-->
        <div>
          <h1 class="text-2x1 font-bold text-[#2f5385]">Dashboard</h1>
          <p class="text-sm text-gray-500 mt-1">
            Welcome back, <?=$_SESSION['staff_role']?>
          </p>
        </div>
        <!--CODE BORDER-->
        <div class="flex items-center gap-3">
          <span class="text-sm text-[#9FA2B2]" >
            ID: <?=$_SESSION['user_id']?>
          </span>
          <div class="w-10 h-10 flex items-center justify-center rounded-full bg-[#3EDCDE] text-white font-semibold">
            <?=strtoupper(substr($_SESSION['staff_role'], 0 ,1))?>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="p-6 flex-1 space-y-6">
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          
          <!-- Patients -->
          <div class="app-card p-6 flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">Active Patients</p>
              <h2 class="text-3xl font-bold text-[#2F5395] mt-2"><?php echo $active_patients ?></h2>
              <p class="text-xs text-gray-400 mt-2">Currently registered and active</p>
            </div>

            <div class="w-12 h-12 rounded-full bg-[#3EDCDE]/20 flex items-center justify-center shrink-0 text-[#2F5395]">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M16 14a4 4 0 10-8 0m8 0a4 4 0 10-8 0m8 0v1a2 2 0 002 2h1m-11-3v1a2 2 0 01-2 2H4" />
              </svg>
            </div>
          </div>

          <!-- Appointments Today -->
          <div class="app-card p-6 flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">Appointments Today</p>
              <h2 class="text-3xl font-bold text-[#2F5395] mt-2"><?php echo $appointments_today ?></h2>
              <p class="text-xs text-gray-400 mt-2">Scheduled for today</p>
            </div>

            <div class="w-12 h-12 rounded-full bg-[#3EDCDE]/20 flex items-center justify-center shrink-0 text-[#2F5395]">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>

          <!-- Total Appointments -->
          <div class="app-card p-6 flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">Total Appointments</p>
              <h2 class="text-3xl font-bold text-[#2F5395] mt-2"><?php echo $app_count_total ?></h2>
              <p class="text-xs text-gray-400 mt-2">All appointments in the system</p>
            </div>

            <div class="w-12 h-12 rounded-full bg-[#3EDCDE]/20 flex items-center justify-center shrink-0">
              <div class="w-12 h-12 rounded-full bg-[#3EDCDE]/20 flex items-center justify-center shrink-0 text-[#2F5395]">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>

          </div>

          <!-- PENDING REQUESTS -->
          <div class="app-card p-6 flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">Pending Requests</p>
              <p class="text-3xl font-bold text-[#2F5395] mt-2"><?= $pending_count ?></p>
              <p class="text-xs text-gray-400 mt-2">Awaiting staff review</p>
            </div>

            <div class="w-12 h-12 bg-[#3EDCDE]/20 rounded-full flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-[#2F5395]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8v4l3 3M12 2a10 10 0 100 20 10 10 0 000-20z" />
              </svg>
            </div>
          </div>

          <!-- COMPLETED APPOINTMENTS -->
          <div class="app-card p-6 flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">Completed Appointments</p>
              <p class="text-3xl font-bold text-[#2F5395] mt-2"><?= $completed_count ?></p>
              <p class="text-xs text-gray-400 mt-2">Finished successfully</p>
            </div>

            <div class="w-12 h-12 bg-[#3EDCDE]/20 rounded-full flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-[#2F5395]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M5 13l4 4L19 7" />
              </svg>
            </div>
          </div>

          <!-- Top Service -->

          <div class="app-card p-6 flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">Top Service</p>
              <p class="text-2xl font-bold text-[#2F5395] mt-2"><?= htmlspecialchars($top_service) ?></p>
              <p class="text-xs text-gray-400 mt-2">Most requested by patients</p>
            </div>

            <div class="w-12 h-12 bg-[#3EDCDE]/20 rounded-full flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-[#2F5395]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l2.036 6.26h6.586c.969 0 1.371 1.24.588 1.81l-5.33 3.874 2.036 6.26c.3.921-.755 1.688-1.54 1.118L12 18.347l-5.327 3.902c-.785.57-1.84-.197-1.54-1.118l2.036-6.26-5.33-3.874c-.783-.57-.38-1.81.588-1.81h6.586l2.036-6.26z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- End of Analytics Cards -->
        
        <!-- Reports / Appointment Activity Table -->
        <div class="app-card p-6">

          <!-- Header -->
          <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h2 class="text-xl font-bold text-[#2F5395]">Appointment Reports</h2>
              <p class="text-sm text-gray-500">Filter and review appointment activity</p>
            </div>

            <div class="flex gap-3">
              <button
                type="button"
                onclick="printReport()"
                class="px-5 py-2 rounded-xl bg-gray-200 text-gray-700 font-medium hover:bg-gray-300 transition"
              >
                Print Report
              </button>

              <a
                href="dashboard.php?export=csv&<?= http_build_query($_GET) ?>"
                class="px-5 py-2 rounded-xl bg-[#2F5395] text-white font-medium hover:bg-[#26457C] transition"
              >
                Export CSV
              </a>
            </div>
          </div>

          <!-- Filter Form -->
          <form method="GET" class="grid md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            
            <!-- Doctor -->
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Doctor</label>
              <select name="doctor" class="w-full border border-[#D6E2F0] rounded-xl px-3 py-2">
                <option value="">All Doctors</option>
                <?php foreach ($doctor_list as $doctor): ?>
                  <option value="<?= htmlspecialchars($doctor['staff_id']) ?>"
                    <?= ($filter_doctor === $doctor['staff_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Patient -->
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Patient</label>
              <select name="patient" class="w-full border border-[#D6E2F0] rounded-xl px-3 py-2">
                <option value="">All Patients</option>
                <?php foreach ($patient_list as $patient): ?>
                  <option value="<?= htmlspecialchars($patient['patient_id']) ?>"
                    <?= ($filter_patient === $patient['patient_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Status -->
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Status</label>
              <select name="status" class="w-full border border-[#D6E2F0] rounded-xl px-3 py-2">
                <option value="">All Statuses</option>
                <option value="Pending" <?= ($filter_status === 'Pending') ? 'selected' : '' ?>>Pending</option>
                <option value="Approved" <?= ($filter_status === 'Scheduled') ? 'selected' : '' ?>>Scheduled</option>
                <option value="Completed" <?= ($filter_status === 'Completed') ? 'selected' : '' ?>>Completed</option>
                <option value="Cancelled" <?= ($filter_status === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
              </select>
            </div>

            <!-- Booked By -->
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Booked By</label>
              <select name="receptionist" class="w-full border border-[#D6E2F0] rounded-xl px-3 py-2">
                <option value="">All Staff</option>
                <?php foreach ($receptionist_list as $staff): ?>
                  <option value="<?= htmlspecialchars($staff['staff_id']) ?>"
                    <?= ($filter_receptionist === $staff['staff_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name'] . ' (' . $staff['staff_role'] . ')') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Date -->
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-1">Date</label>
              <input 
                type="date" 
                name="date" 
                value="<?= htmlspecialchars($filter_date) ?>"
                class="w-full border border-[#D6E2F0] rounded-xl px-3 py-2"
              >
            </div>

            <!-- Buttons -->
            <div class="lg:col-span-5 flex gap-3 mt-2">
              <button type="submit"
                class="px-5 py-2 rounded-xl bg-[#2F5395] text-white font-medium hover:bg-[#26457C] transition">
                Apply Filters
              </button>

              <a href="dashboard.php"
                class="px-5 py-2 rounded-xl bg-gray-200 text-gray-700 font-medium hover:bg-gray-300 transition">
                Reset
              </a>
            </div>
          </form>

          <!-- Table -->
          <div id="report-print-area" class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
              <thead>
                <tr class="border-b border-[#D6E2F0] text-[#2F5395]">
                  <th class="py-3 px-3 font-semibold">#</th>
                  <th class="py-3 px-3 font-semibold">Appointment ID</th>
                  <th class="py-3 px-3 font-semibold">Doctor</th>
                  <th class="py-3 px-3 font-semibold">Patient</th>
                  <th class="py-3 px-3 font-semibold">Service</th>
                  <th class="py-3 px-3 font-semibold">Date</th>
                  <th class="py-3 px-3 font-semibold">Status</th>
                  <th class="py-3 px-3 font-semibold">Booked By</th>
                </tr>
              </thead>

              <tbody>
                <?php if ($report_result && mysqli_num_rows($report_result) > 0): ?>
                  <?php $row_number = $offset + 1; ?>
                  <?php while ($report = mysqli_fetch_assoc($report_result)): ?>
                    <tr class="border-b border-gray-100 hover:bg-[#F8FBFF] transition">
                      <!-- ENTRY -->
                      <td class="py-3 px-3 font-medium text-gray-500">
                        <?= $row_number++ ?>
                      </td>
                      <!-- APPOINTMENT ID -->
                      <td class="py-3 px-3 font-medium text-gray-500">
                        <?= htmlspecialchars($report['appointment_id'] ?? 'N/A') ?>
                      </td>
                      <!-- DOCTOR NAME-->
                      <td class="py-3 px-3">
                        <?= htmlspecialchars(trim(($report['doctor_first_name'] ?? '') . ' ' . ($report['doctor_last_name'] ?? ''))) ?: 'N/A' ?>
                      </td>
                      <!-- PATIENT NAME -->
                      <td class="py-3 px-3">
                        <?= htmlspecialchars(trim(($report['patient_first_name'] ?? '') . ' ' . ($report['patient_last_name'] ?? ''))) ?: 'N/A' ?>
                      </td>
                      <!-- SERVICE TYPE -->
                      <td class="py-3 px-3">
                        <?= htmlspecialchars($report['dental_service_type'] ?? 'N/A') ?>
                      </td>
                      <!-- DATE/TIME -->
                      <td class="py-3 px-3 whitespace-nowrap">
                        <?= !empty($report['scheduled_datetime']) 
                            ? date('M d, Y h:i A', strtotime($report['scheduled_datetime'])) 
                            : 'N/A' ?>
                      </td>
                      <!-- STATUS -->
                      <td class="py-3 px-3">
                        <?php
                          $status = strtolower($report['status'] ?? '');
                          $status_class = 'bg-gray-100 text-gray-600';

                          if ($status === 'scheduled') {
                            $status_class = 'bg-green-100 text-green-700';
                          } elseif ($status === 'pending') {
                            $status_class = 'bg-yellow-100 text-yellow-700';
                          } elseif ($status === 'cancelled' || $status === 'declined') {
                            $status_class = 'bg-red-100 text-red-700';
                          } elseif ($status === 'completed') {
                            $status_class = 'bg-blue-100 text-blue-700';
                          } elseif ($status === 'reschedule requested') {
                            $status_class = 'bg-yellow-100 text-yellow-700';
                          }
                        ?>

                        <span class="px-3 py-1 rounded-full text-xs font-medium <?= $status_class ?>">
                          <?= htmlspecialchars($report['status'] ?? 'N/A') ?>
                        </span>
                      </td>

                      <td class="py-3 px-3">
                        <?= htmlspecialchars(trim(($report['receptionist_first_name'] ?? '') . ' ' . ($report['receptionist_last_name'] ?? ''))) ?: 'N/A' ?>
                      </td>
                    </tr>
                    <!-- END OF TABLE CONTENT -->
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="7" class="py-6 text-center text-gray-500">
                      No appointment records found.
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <?php if ($total_pages > 1): ?>
            <div class="flex flex-wrap items-center justify-between gap-3 mt-6">
              
              <div class="text-sm text-gray-500">
                Showing page <?= $current_page ?> of <?= $total_pages ?>
                (<?= $total_records ?> total records)
              </div>

              <div class="flex flex-wrap items-center gap-2">
                <?php
                  $query_params = $_GET;
                ?>

                <!-- Prev -->
                <?php if ($current_page > 1): ?>
                  <?php $query_params['page'] = $current_page - 1; ?>
                  <a href="?<?= http_build_query($query_params) ?>"
                    class="px-4 py-2 rounded-xl bg-gray-200 text-gray-700 hover:bg-gray-300 transition">
                    Prev
                  </a>
                <?php endif; ?>

                <!-- Page numbers -->
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                  <?php $query_params['page'] = $i; ?>
                  <a href="?<?= http_build_query($query_params) ?>"
                    class="px-4 py-2 rounded-xl transition <?= $i === $current_page ? 'bg-[#2F5395] text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                    <?= $i ?>
                  </a>
                <?php endfor; ?>

                <!-- Next -->
                <?php if ($current_page < $total_pages): ?>
                  <?php $query_params['page'] = $current_page + 1; ?>
                  <a href="?<?= http_build_query($query_params) ?>"
                    class="px-4 py-2 rounded-xl bg-gray-200 text-gray-700 hover:bg-gray-300 transition">
                    Next
                  </a>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>

        </div>

        <!-- End of Report Card -->
      </main>
    </div>
    <!-- Printing script for reports -->
    <script>
      function printReport() {
        const printContents = document.getElementById('report-print-area').innerHTML;
        const originalContents = document.body.innerHTML;

        document.body.innerHTML = `
          <div style="padding: 24px; font-family: Arial, sans-serif;">
            <h2 style="margin-bottom: 6px;">Curaline Appointment Reports</h2>
            <p style="margin-bottom: 20px; color: #666;">Generated on ${new Date().toLocaleString()}</p>
            ${printContents}
          </div>
        `;

        window.print();
        document.body.innerHTML = originalContents;
        window.location.reload();
      }
      </script>
  </body>
</html>