<?php
global $conn;
require "db.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

$conn->query("CREATE TABLE IF NOT EXISTS password_resets (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, token_hash CHAR(64) NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(user_id), INDEX(token_hash)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$type = ($_GET["type"] ?? "user") === "admin" ? "admin" : "user";
$title = ($type==="admin") ? "نسيت كلمة سر المدير" : "نسيت كلمة السر";

$msg = "";
$link = "";

if ($_SERVER["REQUEST_METHOD"]==="POST") {
  $email = trim($_POST["email"] ?? "");
  if ($email==="") {
    $msg = "اكتبي الإيميل.";
  } else {
    // دور على المستخدم
    if ($type==="admin") {
      $stmt=$conn->prepare("SELECT id FROM users WHERE email=? AND role='admin' LIMIT 1");
    } else {
      $stmt=$conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    }
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $u=$stmt->get_result()->fetch_assoc();

    if (!$u) {
      // ما نفضح إذا الإيميل موجود أو لا
      $msg = "إذا الإيميل موجود، رح يطلعلك رابط إعادة تعيين.";
    } else {
      $user_id=(int)$u["id"];
      $token = bin2hex(random_bytes(16)); // 32 chars
      $token_hash = hash("sha256",$token);
      $expires = (new DateTime("+30 minutes"))->format("Y-m-d H:i:s");

      $ins=$conn->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?,?,?)");
      $ins->bind_param("iss",$user_id,$token_hash,$expires);
      $ins->execute();

      $base = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]==="on" ? "https" : "http")
            . "://".$_SERVER["HTTP_HOST"].dirname($_SERVER["REQUEST_URI"]);
      $link = $base."/reset_password.php?token=".$token."&type=".$type;
      $msg = "تمام ✅ هذا رابط إعادة التعيين (للتجربة بالمشروع).";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="stylesheet" href="style.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
  <style>
    body{background:#fdf8f5}
    .wrap{max-width:520px;margin:40px auto;padding:0 16px}
    .card{background:#fff;border-radius:18px;padding:22px;box-shadow:0 10px 30px rgba(0,0,0,0.08)}
    .btn{background:#6d3a75;color:#fff;border:none;border-radius:999px;padding:10px 18px;cursor:pointer;font-weight:800}
    .btn:hover{background:#4b2c4f}
    input{width:100%;padding:12px;border-radius:12px;border:1px solid #ddd;font-family:'Cairo',sans-serif}
    .msg{padding:12px;border-radius:12px;font-weight:800;margin:10px 0}
    .ok{background:#d4edda;color:#155724}
    .muted{color:#777}
    a{word-break:break-all}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div style="font-weight:900;color:#4b2c4f;font-size:1.25rem"><?= htmlspecialchars($title) ?></div>
      <div class="muted" style="margin-top:6px">اكتبي إيميلك ورح نطلعلك رابط تغيير كلمة السر.</div>

      <?php if($msg!==""): ?>
        <div class="msg ok"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <?php if($link!==""): ?>
        <div class="msg ok">
          <div>الرابط:</div>
          <a href="<?= htmlspecialchars($link) ?>"><?= htmlspecialchars($link) ?></a>
        </div>
      <?php endif; ?>

      <form method="POST" style="margin-top:12px;display:grid;gap:12px">
        <div>
          <label>الإيميل</label>
          <input type="email" name="email" required placeholder="example@email.com">
        </div>
        <button class="btn" type="submit">إرسال رابط إعادة التعيين</button>
      </form>

      <div style="margin-top:12px">
        <a href="<?= ($type==="admin") ? "admin-login.php" : "login.php" ?>">رجوع لتسجيل الدخول</a>
      </div>
    </div>
  </div>
</body>
</html>
