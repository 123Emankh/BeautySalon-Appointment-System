<?php
global $conn;
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require "db.php";

$error = "";

// POST فقط
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $full_name = trim($_POST["full_name"] ?? "");
  $phone     = trim($_POST["phone"] ?? "");
  $email     = trim($_POST["email"] ?? "");
  $password  = $_POST["password"] ?? "";

  if ($full_name === "" || $phone === "" || $password === "") {
    $error = "في بيانات ناقصة.";
  } elseif (strlen($password) < 6) {
    $error = "كلمة المرور لازم تكون 6 أحرف على الأقل.";
  } else {

    $email_db = ($email === "") ? null : $email;

    $conn->query("
      CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(120) NOT NULL,
        phone VARCHAR(20) NOT NULL UNIQUE,
        email VARCHAR(120) NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('user','admin') NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
      INSERT INTO users (full_name, phone, email, password_hash, role)
      VALUES (?, ?, ?, ?, 'user')
    ");
    $stmt->bind_param("ssss", $full_name, $phone, $email_db, $password_hash);

    try {
      $stmt->execute();
      header("Location: login.php?registered=1");
      exit();
    } catch (mysqli_sql_exception $e) {
      if ($e->getCode() == 1062) $error = "رقم الجوال أو الإيميل مستخدمين قبل.";
      else $error = "صار خطأ غير متوقع.";
    }
  }
}

// لو GET: اعرض فورم تسجيل
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>إنشاء حساب - إمالين صالون</title>
  <link rel="stylesheet" href="style.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
<div class="login-container">
  <div class="login-box">
    <h1>✨ إمالين صالون ✨</h1>
    <h2>انضمي إلينا اليوم</h2>

    <?php if ($error !== ""): ?>
      <div style="background:#f8d7da;color:#721c24;padding:12px;border-radius:12px;margin:10px 0;font-weight:700;">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form action="register.php" method="POST">
      <div class="input-group">
        <label>الاسم الكامل</label>
        <input type="text" name="full_name" required placeholder="فاطمة أحمد"/>
      </div>

      <div class="input-group">
        <label>رقم الجوال</label>
        <input type="tel" name="phone" required placeholder="05xxxxxxxx"/>
      </div>

      <div class="input-group">
        <label>الإيميل (اختياري)</label>
        <input type="email" name="email" placeholder="example@gmail.com"/>
      </div>

      <div class="input-group">
        <label>كلمة المرور</label>
        <input type="password" name="password" required minlength="6" placeholder="6 أحرف على الأقل"/>
      </div>

      <button type="submit" class="btn-primary">إنشاء الحساب</button>
    </form>

    <p class="signup-link">
      لديكِ حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a>
    </p>
  </div>
</div>
</body>
</html>
