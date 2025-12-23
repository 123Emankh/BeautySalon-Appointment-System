<?php
global $conn;
session_start();
require "db.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $identifier = trim($_POST["identifier"] ?? "");
  $password   = $_POST["password"] ?? "";

  if ($identifier === "" || $password === "") {
    $error = "اكتبي الإيميل/الجوال وكلمة المرور.";
  } else {
    $stmt = $conn->prepare("
      SELECT id, full_name, password_hash, role
      FROM users
      WHERE (email = ? OR phone = ?)
      LIMIT 1
    ");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // لازم يكون موجود + باسورد صح + role = admin
    if (!$user || !password_verify($password, $user["password_hash"]) || $user["role"] !== "admin") {
      $error = "البيانات غير صحيحة، حاولي مرة أخرى.";
    } else {
      $_SESSION["user_id"] = (int)$user["id"];
      $_SESSION["name"]    = $user["full_name"];
      $_SESSION["role"]    = $user["role"];

      header("Location: admin-dashboard.php"); // غيّريها حسب اسم صفحة الأدمن عندك
      exit();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>تسجيل دخول المدير - Emalen Salon</title>
  <link rel="stylesheet" href="style.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
<div class="login-container">
  <div class="login-box">
    <h1>✨ Emalen Salon ✨</h1>
    <h2>تسجيل دخول المدير</h2>

    <?php if ($error !== ""): ?>
      <div style="background:#f8d7da;color:#721c24;padding:12px;border-radius:12px;margin:10px 0;font-weight:700;">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="admin-login.php">
      <div class="input-group">
        <label>الإيميل أو رقم الجوال</label>
        <input type="text" name="identifier" required placeholder="admin@emalen.com أو 05xxxxxxxx">
      </div>

      <div class="input-group">
        <label>كلمة المرور</label>
        <input type="password" name="password" required placeholder="••••••••">
      </div>

      <button type="submit" class="btn-primary">تسجيل الدخول</button>
      <div style="margin-top:10px">
        <a href="forgot_password.php?type=admin">نسيتِ كلمة سر المدير؟</a>
      </div>

    </form>

    <p class="guest-link" style="margin-top:12px">
      <a href="login.php">رجوع لتسجيل دخول المستخدم</a>
    </p>
  </div>
</div>
</body>
</html>
