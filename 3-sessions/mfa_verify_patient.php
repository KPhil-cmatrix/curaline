<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../2-backend/db.php';

use OTPHP\TOTP;

if (
    !isset($_SESSION['pending_mfa_user_id']) ||
    !isset($_SESSION['pending_mfa_role']) ||
    $_SESSION['pending_mfa_role'] !== 'patient'
) {
    header("Location: ../login.php?role=patient");
    exit;
}

$user_id = $_SESSION['pending_mfa_user_id'];
$error = null;

$stmt = $conn->prepare("
    SELECT patient_id, first_name, mfa_secret
    FROM patient_info
    WHERE patient_id = ?
    LIMIT 1
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    session_unset();
    session_destroy();
    header("Location: ../login.php?role=patient");
    exit;
}

if (empty($user['mfa_secret'])) {
    header("Location: mfa_setup_patient.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');

    if ($code === '') {
        $error = "Please enter your 6-digit authentication code.";
    } elseif (!preg_match('/^\d{6}$/', $code)) {
        $error = "Enter a valid 6-digit code.";
    } else {
        try {
            $totp = TOTP::create($user['mfa_secret']);
            $isValid = $totp->verify($code, null, 1);

            if ($isValid) {
                $_SESSION['user_id'] = $user['patient_id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['user_type'] = 'patient';
                $_SESSION['logged_in'] = true;

                unset($_SESSION['pending_mfa_user_id']);
                unset($_SESSION['pending_mfa_role']);
                unset($_SESSION['pending_mfa_first_name']);
                unset($_SESSION['pending_mfa_secret']);

                header("Location: ../4-patient/patient_dashboard.php");
                exit;
            } else {
                $error = "Invalid authentication code. Please try again.";
            }
        } catch (Throwable $e) {
            $error = "Unable to verify MFA code right now.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Curaline – Patient MFA Verify</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../1-assets/ui.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-[#EEF3FA] to-[#C9D8F0] flex items-center justify-center px-4">

  <div class="app-card p-8 w-full max-w-md">
    <div class="text-center mb-6">
      <h1 class="text-2xl font-bold text-[#2F5395]">Verify Your Identity</h1>
      <p class="text-sm text-gray-500 mt-2">
        Enter the 6-digit code from your authenticator app.
      </p>
    </div>

    <?php if ($error): ?>
      <div class="mb-4 rounded-lg bg-red-100 text-red-700 px-4 py-3 text-sm">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label for="code" class="block text-sm font-medium text-[#2F5395] mb-2">
          Authentication Code
        </label>
        <input
          id="code"
          name="code"
          type="text"
          inputmode="numeric"
          pattern="\d{6}"
          maxlength="6"
          autocomplete="one-time-code"
          placeholder="Enter 6-digit code"
          class="w-full border border-[#8FBFE0] rounded-lg p-3 text-center tracking-[0.35em] focus:outline-none focus:ring-2 focus:ring-[#3EDCDE]"
          required
        >
      </div>

      <button
        type="submit"
        class="w-full bg-[#2F5395] text-white py-3 rounded-lg font-semibold hover:bg-[#3EDCDE] hover:text-[#2F5395] transition-all duration-200"
      >
        Verify & Continue
      </button>
    </form>

    <p class="text-xs text-gray-400 mt-4 text-center">
      Signing in as <?= htmlspecialchars($user['patient_id']) ?>
    </p>
  </div>

</body>
</html>