<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../2-backend/db.php';

use OTPHP\TOTP;

if (!isset($_SESSION['pending_mfa_user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['pending_mfa_user_id'];
$error = null;

// Generate a secret once per page load
if (!isset($_SESSION['pending_mfa_secret'])) {
    $totp = TOTP::create();
    $totp->setLabel($user_id);
    $totp->setIssuer('Curaline');
    $_SESSION['pending_mfa_secret'] = $totp->getSecret();
}

$secret = $_SESSION['pending_mfa_secret'];

$totp = TOTP::create($secret);
$totp->setLabel($user_id);
$totp->setIssuer('Curaline');

$otpauth = $totp->getProvisioningUri();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');

    if ($code === '') {
        $error = "Please enter the 6-digit code from your authenticator app.";
    } elseif (!preg_match('/^\d{6}$/', $code)) {
        $error = "Enter a valid 6-digit code.";
    } else {
        $isValid = $totp->verify($code, null, 1);

        if ($isValid) {
            $stmt = $conn->prepare("UPDATE staff_info SET mfa_secret = ?, mfa_enabled = 1 WHERE staff_id = ?");
            $stmt->bind_param("ss", $secret, $user_id);
            $stmt->execute();

            unset($_SESSION['pending_mfa_secret']);

            header("Location: mfa_verify.php");
            exit;
        } else {
            $error = "The code you entered is incorrect. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Curaline – Set Up MFA</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../1-assets/ui.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-[#EEF3FA] to-[#C9D8F0] flex items-center justify-center px-4">

  <div class="app-card p-8 w-full max-w-md">
    <div class="text-center mb-6">
      <h1 class="text-2xl font-bold text-[#2F5395]">Set Up MFA</h1>
      <p class="text-sm text-gray-500 mt-2">
        Scan the QR code in Google Authenticator, then enter the 6-digit code to confirm setup.
      </p>
    </div>

    <?php if ($error): ?>
      <div class="mb-4 rounded-lg bg-red-100 text-red-700 px-4 py-3 text-sm">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <div class="flex justify-center mb-4">
      <img
        src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?= urlencode($otpauth) ?>"
        alt="MFA QR Code"
      >
    </div>

    <p class="text-xs text-gray-500 text-center mb-2">Manual setup key</p>
    <div class="mb-6 rounded-lg bg-gray-100 px-4 py-3 text-center font-mono text-sm break-all">
      <?= htmlspecialchars($secret) ?>
    </div>

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
          placeholder="Enter 6-digit code"
          class="w-full border border-[#8FBFE0] rounded-lg p-3 text-center tracking-[0.35em] focus:outline-none focus:ring-2 focus:ring-[#3EDCDE]"
          required
        >
      </div>

      <button
        type="submit"
        class="w-full bg-[#2F5395] text-white py-3 rounded-lg font-semibold hover:bg-[#3EDCDE] hover:text-[#2F5395] transition-all duration-200"
      >
        Verify & Save
      </button>
    </form>
  </div>

</body>
</html>