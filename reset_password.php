<?php
global $conn;
require "db.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

$conn->query("CREATE TABLE IF NOT EXISTS password_resets (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, token_hash CHAR(64) NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(user_id), INDEX(token_hash)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$type = ($_GET["type"] ?? "user") === "admin" ? "admin" : "user";
$token = $_GET["token"] ?? "";
$title = "إعادة تعيين كلمة السر";

$error="";
$ok="";

if ($token==="") die("Invalid token");

$token_hash = hash("sha256",$token);

$stmt=$conn->prepare("
  SELECT pr.id AS reset_id, pr.user_id, pr.expires_at, pr.used_at, u.role
  FROM password_resets pr
  JOIN users u ON u.id = pr.user_id
  WHERE pr.token_hash = ?
  ORDER BY pr.id DESC
  LIMIT 1
");
$stmt->bind_param("s",$token_hash);
$stmt->execute();
$row=$stmt->get_result()->fetch_assoc();

if(!$row){
  $error="الرابط غير صحيح.";
} else {
  if ($type==="admin" && ($row["role"] ?? "")!=="admin") $error="الرابط هذا مش للمدير.";
  if ($row["used_at"] !== null) $error="هذا الرابط مستخدم قبل.";
  if ($error==="") {
    $exp = strtotime($row["expires_at"]);
    if ($exp !== false && $exp < time()) $error="الرابط انتهت صلاحيته.";
  }
}

if ($_SERVER["REQUEST_METHOD"]==="POST" && $error==="") {
  $p1 = $_POST["password"] ?? "";
  $p2 = $_POST["password2"] ?? "";
  if ($p1==="" || strlen($p1)<6) $error="كلمة السر لازم تكون 6 أحرف على الأقل.";
  elseif ($p1!==$p2) $error="كلمتين السر مش نفس الشي.";
  else {
    $hash = password_hash($p1, PASSWORD_DEFAULT);
$conn->begin_transaction();
try{
  $up=$conn->prepare("UPDATE users SET password_hash=? WHERE id=? LIMIT 1");
  $uid=(int)$row["user_id"];
  $up->bind_param("si",$hash,$uid);
  $up->execute();

  $up2=$conn->prepare("UPDATE password_resets SET used_at=NOW() WHERE id=? LIMIT 1");
  $rid=(int)$row["reset_id"];
  $up2->bind_param("i",$rid);
  $up2->execute();

  $conn->commit();
  $ok="تم تغيير كلمة السر ✅ تقدري تسجلي دخول هلا.";
}catch(Exception $e){
  $conn->rollback();
  $error="صار خطأ: ".$e->getMessage();
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
    .err{background:#f8d7da;color:#721c24}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div style="font-weight:900;color:#4b2c4f;font-size:1.25rem"><?= htmlspecialchars($title) ?></div>

      <?php if($error!==""): ?>
        <div class="msg err"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if($ok!==""): ?>
        <div class="msg ok"><?= htmlspecialchars($ok) ?></div>
        <div style="margin-top:10px">
          <a href="<?= ($type==="admin") ? "admin-login.php" : "login.php" ?>">سجلي دخول</a>
        </div>
      <?php elseif($error===""): ?>
        <form method="POST" style="margin-top:12px;display:grid;gap:12px">
          <div>
            <label>كلمة السر الجديدة</label>
            <input type="password" name="password" required>
          </div>
          <div>
            <label>تأكيد كلمة السر</label>
            <input type="password" name="password2" required>
          </div>
          <button class="btn" type="submit">تغيير كلمة السر</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
