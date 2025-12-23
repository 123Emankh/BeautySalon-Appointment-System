<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require "db.php";

$error = "";
$registered = isset($_GET["registered"]);

// صفحة الرجوع بعد تسجيل الدخول
$next = $_GET["next"] ?? "emalen.php";

// صفحات مسموح التحويل إلها (حماية)
$allowed = ["booking.php", "shop.php", "emalen.php", "my-orders.php"];
if (!in_array($next, $allowed, true)) {
    $next = "emalen.php";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identifier = trim($_POST["identifier"] ?? "");
    $password   = $_POST["password"] ?? "";

    if ($identifier === "" || $password === "") {
        $error = "اكتبي الإيميل/الجوال وكلمة المرور.";
    } else {

        $stmt = $conn->prepare("
            SELECT id, full_name, password_hash, role
            FROM users
            WHERE email = ? OR phone = ?
            LIMIT 1
        ");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || !password_verify($password, $user["password_hash"])) {
            $error = "بيانات الدخول غير صحيحة.";
        } else {
            $_SESSION["user_id"] = (int)$user["id"];
            $_SESSION["name"]    = $user["full_name"];
            $_SESSION["role"]    = $user["role"];

            // 🔀 تحويل حسب الدور
            if (($user["role"] ?? "") === "admin") {
                header("Location: admin-dashboard.php");
                exit();
            } else {
                header("Location: " . $next);
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>تسجيل الدخول - إمالين صالون</title>
  <link rel="stylesheet" href="style.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
<div class="login-container">
  <div class="login-box">
    <h1>✨ إمالين صالون ✨</h1>
    <h2>مرحباً بكِ مرة أخرى</h2>

    <?php if ($registered): ?>
      <div style="background:#d4edda;color:#155724;padding:12px;border-radius:12px;margin:10px 0;font-weight:700;">
        تم إنشاء الحساب ✅ الآن سجّلي دخولك.
      </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
      <div style="background:#f8d7da;color:#721c24;padding:12px;border-radius:12px;margin:10px 0;font-weight:700;">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST"
          action="login.php<?= isset($_GET['next']) ? '?next='.urlencode($_GET['next']) : '' ?>">

      <div class="input-group">
        <label>رقم الجوال أو الإيميل</label>
        <input type="text" name="identifier" required placeholder="05xxxxxxxx أو example@gmail.com"/>
      </div>

      <div class="input-group">
        <label>كلمة المرور</label>
        <input type="password" name="password" required placeholder="••••••••"/>
      </div>

      <button type="submit" class="btn-primary">تسجيل الدخول</button>

      <div style="margin-top:10px">
        <a href="forgot_password.php?type=user">نسيتِ كلمة السر؟</a>
      </div>
    </form>

    <p class="signup-link">
      ليس لديكِ حساب؟ <a href="register.php">إنشاء حساب جديد</a>
    </p>

    <p class="guest-link">
      <a href="guest.php">الدخول كزائر (بدون حساب)</a>
    </p>
  </div>
</div>
</body>
</html>
