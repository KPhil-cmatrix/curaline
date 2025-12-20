<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Select Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-[#8FBFE0] flex items-center justify-center min-h-screen">

  <div class="bg-white p-8 rounded-xl shadow-lg space-y-6 w-full max-w-md text-center">

    <h1 class="text-3xl font-extrabold text-[#2F5395]">
      Curaline Login
    </h1>

    <!-- Staff Login -->
    <a href="login.php?role=staff"
      class="block w-full bg-[#2F5395] text-white py-3 rounded-lg hover:bg-[#3EDCDE]">
      Staff Login
    </a>

    <!-- Patient Login -->
    <a href="login.php?role=patient"
      class="block w-full bg-[#2F5395] text-white py-3 rounded-lg hover:bg-[#3EDCDE]">
      Patient Login
    </a>

  </div>

</body>
</html>
